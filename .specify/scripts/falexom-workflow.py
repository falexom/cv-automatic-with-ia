#!/usr/bin/env python3
"""Project-local Git/Spec Kit operations. No model calls, merge or push."""
import argparse
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
GIT = '/usr/bin/git'
SPEC = re.compile(r'[0-9]{3,}-[a-z0-9]+(?:-[a-z0-9]+)*')


def fail(message):
    raise RuntimeError(message)


def environment():
    env = {k: v for k, v in os.environ.items() if not k.startswith(('GIT_', 'SPECIFY_'))}
    env.update(GIT_OPTIONAL_LOCKS='0', GIT_TERMINAL_PROMPT='0')
    return env


def git(*args):
    return subprocess.check_output([GIT, '-c', 'core.fsmonitor=false', *args],
                                   cwd=ROOT, env=environment()).decode().strip()


def repository():
    if Path(git('rev-parse', '--show-toplevel')).resolve() != ROOT:
        fail('Run the integration in its own repository root')
    if (ROOT / '.git').is_symlink() or not (ROOT / '.git').is_dir():
        fail('Linked worktrees/symlinked Git directories require manual review')
    for name in ['MERGE_HEAD', 'CHERRY_PICK_HEAD', 'REVERT_HEAD', 'rebase-merge', 'rebase-apply', 'index.lock']:
        if (ROOT / '.git' / name).exists():
            fail('Git operation pending: ' + name)
    return git('symbolic-ref', '--quiet', '--short', 'HEAD')


def safe_path(value):
    p = Path(value)
    if p.is_absolute() or not p.parts or any(x in ('..', '.') for x in p.parts):
        fail('Only explicit relative paths are accepted')
    if any(not re.fullmatch(r'[A-Za-z0-9_.-]+', x) for x in p.parts):
        fail('Unsafe path syntax: ' + value)
    for n in range(1, len(p.parts) + 1):
        part = ROOT.joinpath(*p.parts[:n])
        if part.is_symlink():
            fail('Symlink requires manual review: ' + value)
        if part.is_file() and part.stat().st_nlink != 1:
            fail('Hardlink requires manual review: ' + value)
    if not (ROOT / p).resolve().is_relative_to(ROOT):
        fail('Path outside repository')
    return p


def ancestor(left, right):
    result = subprocess.run([GIT, 'merge-base', '--is-ancestor', left, right],
                            cwd=ROOT, env=environment(), capture_output=True)
    if result.returncode not in (0, 1):
        fail('Cannot verify branch ancestry; human preparation required')
    return result.returncode == 0


def prepared_base(ref='HEAD'):
    head, base = git('rev-parse', '--verify', ref), git('rev-parse', '--verify', 'main')
    if not ancestor(base, head):
        if ancestor(head, base):
            fail('BASE_OUTDATED_FAST_FORWARD: SPEC branch is behind main. Stop before development. '
                 'A fast-forward of the SPEC branch is not a merge into main, but still requires '
                 'effective authorization. Request human preparation immediately; do not retry '
                 'denied merge commands through another tool or wrapper.')
        fail('BASE_DIVERGED: SPEC and main have diverged. Stop writes and request a human decision; '
             'no automatic merge, rebase or history rewrite.')
    for relative in ['AGENTS.md', '.specify/memory/constitution.md',
                     '.specify/scripts/falexom-workflow.py']:
        result = subprocess.run([GIT, 'cat-file', '-e', ref + ':' + relative],
                                cwd=ROOT, env=environment(), capture_output=True)
        if result.returncode or (ref == 'HEAD' and not (ROOT / safe_path(relative)).is_file()):
            fail('INTEGRATION_MISSING: ' + relative + '. Stop before development; human preparation required.')
    return {'HEAD': head, 'MAIN': base, 'BASE_READY': True}


def check():
    branch = repository()
    if not SPEC.fullmatch(branch):
        fail('Writes blocked: select the SPEC branch, never main/master or a detached HEAD')
    readiness = prepared_base()
    f = safe_path('.specify/feature.json')
    feature = json.loads((ROOT / f).read_text())['feature_directory']
    if feature != 'specs/' + branch:
        fail('Branch and .specify/feature.json do not match')
    safe_path(feature)
    safe_path(feature + '/spec.md')
    if not (ROOT / feature / 'spec.md').is_file():
        fail('Selected specification is missing')
    for key, expected in [('SPECIFY_FEATURE', branch), ('SPECIFY_FEATURE_DIRECTORY', feature)]:
        actual = os.environ.get(key)
        if actual and actual != expected and actual != str(ROOT / expected):
            fail('Conflicting environment selection: ' + key)
    if os.environ.get('SPECIFY_INIT_DIR') and Path(os.environ['SPECIFY_INIT_DIR']).resolve() != ROOT:
        fail('Conflicting SPECIFY_INIT_DIR')
    return {'BRANCH_NAME': branch, 'FEATURE_DIR': str(ROOT / feature), **readiness}


def clean():
    if git('status', '--porcelain', '--untracked-files=all'):
        fail('Uncommitted work exists; preserve it and resolve manually before switching branches')


def prepare(slug, description):
    if not re.fullmatch(r'[a-z0-9]+(?:-[a-z0-9]+)*', slug) or len(slug) > 80:
        fail('Use a short lowercase feature slug')
    if not description.strip() or description.startswith('-'):
        fail('A feature description is required')
    current = repository()
    if SPEC.fullmatch(current) and current.split('-', 1)[1] == slug:
        return check()
    if current != 'main':
        fail('New SPEC preparation requires clean main; use resume for existing work')
    clean()
    prepared_base()
    if any(os.environ.get(k) for k in ['SPECIFY_FEATURE', 'SPECIFY_FEATURE_DIRECTORY', 'SPECIFY_INIT_DIR']):
        fail('Clear explicit SPECIFY overrides before preparing a new SPEC')
    safe_path('specs')
    names = git('for-each-ref', '--format=%(refname:short)', 'refs/heads/').splitlines()
    names += [p.name for p in (ROOT / 'specs').iterdir() if p.is_dir()] if (ROOT / 'specs').exists() else []
    numbers = [int(name.split('-')[0]) for name in names if SPEC.fullmatch(name)]
    branch = f'{max(numbers, default=0) + 1:03d}-{slug}'
    if branch in git('for-each-ref', '--format=%(refname:short)', 'refs/heads/').splitlines():
        fail('Branch already exists; explicit resume required')
    script = ROOT / '.specify/scripts/bash/create-new-feature.sh'
    args = ['/bin/bash', str(script), '--json', '--short-name', slug,
            '--number', branch.split('-')[0], description]
    dry = json.loads(subprocess.check_output(args[:2] + ['--dry-run'] + args[2:],
                                            cwd=ROOT, env=environment()))
    if dry['BRANCH_NAME'] != branch:
        fail('Spec Kit numbering disagrees with requested branch')
    git('switch', '-c', branch, 'main')
    # The native generator performs all template resolution and feature.json persistence.
    subprocess.run(args, cwd=ROOT, env=environment(), check=True)
    return check()


def resume(name):
    if not SPEC.fullmatch(name):
        fail('Expected NNN-feature-name')
    current = repository()
    if current != name:
        clean()
    if name not in git('for-each-ref', '--format=%(refname:short)', 'refs/heads/').splitlines():
        fail('No existing branch; will not create a duplicate')
    # Validate the target BEFORE switching, selecting context or delegating edits.
    prepared_base('HEAD' if current == name else name)
    for key, expected in [('SPECIFY_FEATURE', name), ('SPECIFY_FEATURE_DIRECTORY', 'specs/' + name)]:
        actual = os.environ.get(key)
        if actual and actual != expected and actual != str(ROOT / expected):
            fail('Conflicting environment selection: ' + key)
    if os.environ.get('SPECIFY_INIT_DIR') and Path(os.environ['SPECIFY_INIT_DIR']).resolve() != ROOT:
        fail('Conflicting SPECIFY_INIT_DIR')
    git('cat-file', '-e', name + ':specs/' + name + '/spec.md')
    pointer = ROOT / safe_path('.specify/feature.json')
    if subprocess.run([GIT, 'check-ignore', '-q', '.specify/feature.json'], cwd=ROOT,
                      env=environment()).returncode != 0:
        fail('feature.json is tracked or not ignored; manual reconciliation required')
    data = json.loads(pointer.read_text()) if pointer.exists() else {}
    if not isinstance(data, dict):
        fail('Invalid feature.json; manual reconciliation required')
    if current != name:
        git('switch', name)
    # On the SAME branch this only selects the explicitly requested ignored pointer.
    # Existing product edits/index are preserved; no stash, reset or merge is attempted.
    if data.get('feature_directory') != 'specs/' + name:
        data['feature_directory'] = 'specs/' + name
        fd, temp = tempfile.mkstemp(prefix='.feature-', dir=pointer.parent)
        try:
            with os.fdopen(fd, 'w') as stream:
                os.fchmod(stream.fileno(), pointer.stat().st_mode & 0o777 if pointer.exists() else 0o644)
                stream.write(json.dumps(data, indent=4) + '\n')
            os.replace(temp, pointer)
        finally:
            if os.path.exists(temp): os.unlink(temp)
    return check()


def selected_paths(paths):
    check()
    if not paths or len(set(paths)) != len(paths):
        fail('Provide distinct explicit file paths, never git add .')
    for value in paths:
        p = safe_path(value)
        low = value.lower()
        if (value == 'AGENTS.md' or p.parts[0] in ['.git', '.opencode', '.codex', '.agents']
                or p.parts[0] == '.specify'
                or any(x in low for x in ['.env', 'credential', 'secret', 'token', 'auth.json', 'cookie'])
                or p.suffix.lower() in ['.pem', '.key', '.db', '.sqlite', '.sqlite3']):
            fail('Sensitive/integration path requires manual review: ' + value)
        if (ROOT / p).is_dir():
            fail('Directories are not accepted: ' + value)
        if git('check-attr', 'filter', '--', value).split(': ')[-1] not in ['unspecified', 'unset']:
            fail('Git content filter requires manual review: ' + value)


def stage(paths):
    selected_paths(paths)
    if git('diff', '--cached', '--name-only'):
        fail('Index already contains staged work; do not mix changes')
    git('add', '--', *paths)
    return {'staged': git('diff', '--cached', '--name-only').splitlines()}


def commit(paths, message):
    selected_paths(paths)
    if set(git('diff', '--cached', '--name-only').splitlines()) != set(paths):
        fail('Index must contain exactly the reviewed paths')
    if git('diff', '--name-only', '--', *paths):
        fail('Selected files changed after staging')
    if not message.strip() or len(message) > 500 or '\n' in message:
        fail('Use a brief single-line commit message')
    # Do not execute unknown hooks or silently disable project controls.
    hooks = subprocess.run([GIT, 'config', '--get', 'core.hooksPath'], cwd=ROOT,
                           env=environment(), stdout=subprocess.PIPE, check=False)
    if hooks.returncode == 0 or any(p.is_file() and os.access(p, os.X_OK) and not p.name.endswith('.sample')
                    for p in (ROOT / '.git/hooks').glob('*')):
        fail('Configured Git hooks require manual commit/review; hooks are not disabled')
    signing = subprocess.run([GIT, 'config', '--bool', '--get', 'commit.gpgSign'],
                             cwd=ROOT, env=environment(), stdout=subprocess.PIPE, check=False)
    if signing.stdout.strip() == b'true':
        fail('Configured commit signing requires manual review; signing is not disabled')
    git('diff', '--cached', '--check')
    git('commit', '-m', message)
    return {'commit': git('rev-parse', '--short', 'HEAD'), 'branch': repository(),
            'integration': 'pending human merge approval; no push'}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='operation', required=True)
    sub.add_parser('check')
    sub.add_parser('preflight')
    p = sub.add_parser('check-create'); p.add_argument('spec')
    p = sub.add_parser('prepare'); p.add_argument('slug'); p.add_argument('description')
    p = sub.add_parser('resume'); p.add_argument('spec')
    p = sub.add_parser('stage'); p.add_argument('paths', nargs='+')
    p = sub.add_parser('commit'); p.add_argument('--message', required=True); p.add_argument('paths', nargs='+')
    a = parser.parse_args()
    if a.operation == 'check': result = check()
    elif a.operation == 'preflight':
        if repository() == 'main':
            clean()
            prepared_base()
            result = {'base': 'main', 'ready': 'new SPEC preparation authorized separately'}
        else:
            result = check()
    elif a.operation == 'check-create':
        if not SPEC.fullmatch(a.spec) or repository() != a.spec:
            fail('Generator must run on its matching SPEC branch, never main')
        prepared_base()
        safe_path('specs/' + a.spec)
        if (ROOT / 'specs' / a.spec).exists():
            fail('Existing SPEC: use resume, do not regenerate')
        result = {'BRANCH_NAME': a.spec}
    elif a.operation == 'prepare': result = prepare(a.slug, a.description)
    elif a.operation == 'resume': result = resume(a.spec)
    elif a.operation == 'stage': result = stage(a.paths)
    else: result = commit(a.paths, a.message)
    print(json.dumps(result))


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, OSError, ValueError, KeyError, subprocess.CalledProcessError) as error:
        print('BLOCKED: ' + str(error), file=sys.stderr)
        sys.exit(1)
