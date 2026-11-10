#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';

const INPUT_LIMIT_BYTES = 2_000_000;
const STDERR_LIMIT_BYTES = 64_000;
const OUTPUT_LIMIT_BYTES = 2_000_000;

function writeResult(payload) {
    process.stdout.write(JSON.stringify(payload));
}

function failure(code, message) {
    writeResult({ ok: false, error: { code, message } });
}

async function readStdin() {
    const chunks = [];
    let size = 0;

    for await (const chunk of process.stdin) {
        size += chunk.length;

        if (size > INPUT_LIMIT_BYTES) {
            throw new Error('La entrada supera el límite permitido.');
        }

        chunks.push(chunk);
    }

    return Buffer.concat(chunks).toString('utf8');
}

function validateInput(input) {
    if (typeof input !== 'object' || input === null || Array.isArray(input)) {
        return 'La entrada no es un objeto JSON.';
    }

    if (typeof input.prompt !== 'string' || input.prompt.trim() === '') {
        return 'Falta el prompt.';
    }

    if (
        typeof input.schema !== 'object' ||
        input.schema === null ||
        Array.isArray(input.schema)
    ) {
        return 'Falta el esquema JSON.';
    }

    if (typeof input.model !== 'string' || input.model.trim() === '') {
        return 'Falta el modelo.';
    }

    if (typeof input.effort !== 'string' || input.effort.trim() === '') {
        return 'Falta el esfuerzo de razonamiento.';
    }

    if (typeof input.binary !== 'string' || input.binary.trim() === '') {
        return 'Falta el binario de Codex.';
    }

    if (!Number.isInteger(input.timeout_ms) || input.timeout_ms <= 0) {
        return 'El tiempo máximo no es válido.';
    }

    return null;
}

function classifyFailure(stderr, model, effort) {
    const text = stderr.slice(-4000).toLowerCase();

    if (
        /not logged in|login required|please log in|unauthorized|401/.test(text)
    ) {
        return [
            'codex_auth',
            'Codex no tiene una sesión de ChatGPT válida. Ejecutá `codex login` y volvé a intentar. No se guardó ningún cambio.',
        ];
    }

    if (
        /model_not_found|model not found|unknown model|unsupported model|does not have access to model|not available for this account|model_reasoning_effort|invalid effort|unsupported reasoning effort/.test(
            text,
        )
    ) {
        return [
            'codex_model',
            `El modelo ${model} o el esfuerzo ${effort} no está disponible para esta cuenta. No se cambió de modelo automáticamente.`,
        ];
    }

    if (
        /output schema|structured output|invalid schema|response_format|json schema/.test(
            text,
        )
    ) {
        return [
            'codex_schema',
            'Codex no pudo producir la salida estructurada solicitada. No se guardó ningún cambio.',
        ];
    }

    return [
        'codex_failed',
        'Codex no pudo completar el análisis. No se guardó ningún cambio.',
    ];
}

async function runCodex(input, workDirectory) {
    const schemaPath = path.join(workDirectory, 'schema.json');
    const outputPath = path.join(workDirectory, 'output.json');

    await writeFile(schemaPath, JSON.stringify(input.schema), 'utf8');

    const args = [
        'exec',
        '-m',
        input.model,
        '-c',
        `model_reasoning_effort="${input.effort}"`,
        '--output-schema',
        schemaPath,
        '--output-last-message',
        outputPath,
        '--ephemeral',
        '--skip-git-repo-check',
        '--ignore-user-config',
        '--ignore-rules',
        '--disable',
        'shell_tool',
        '--disable',
        'unified_exec',
        '--disable',
        'browser_use',
        '--disable',
        'computer_use',
        '--disable',
        'in_app_browser',
        '--disable',
        'image_generation',
        '--disable',
        'apps',
        '-C',
        workDirectory,
        '--sandbox',
        'read-only',
    ];

    return new Promise((resolve) => {
        const child = spawn(input.binary, args, {
            cwd: workDirectory,
            env: process.env,
            stdio: ['pipe', 'pipe', 'pipe'],
        });

        let stdout = '';
        let stderr = '';
        let settled = false;
        let timedOut = false;

        const timer = setTimeout(() => {
            timedOut = true;
            child.kill('SIGTERM');
        }, input.timeout_ms);

        const killTimer = setTimeout(() => {
            if (!settled) {
                child.kill('SIGKILL');
            }
        }, input.timeout_ms + 5_000);

        const finish = (result) => {
            if (settled) {
                return;
            }

            settled = true;
            clearTimeout(timer);
            clearTimeout(killTimer);
            resolve(result);
        };

        child.stdin.on('error', () => {});

        child.stdout.on('data', (chunk) => {
            if (stdout.length < STDERR_LIMIT_BYTES) {
                stdout += chunk.toString('utf8');
            }
        });

        child.stderr.on('data', (chunk) => {
            if (stderr.length < STDERR_LIMIT_BYTES) {
                stderr += chunk.toString('utf8');
            }
        });

        child.on('error', (error) => {
            if (error.code === 'ENOENT') {
                finish({
                    ok: false,
                    error: {
                        code: 'codex_unavailable',
                        message:
                            'No se encontró el CLI de Codex. Verificá la instalación y la configuración de CODEX_BINARY.',
                    },
                });

                return;
            }

            finish({
                ok: false,
                error: {
                    code: 'codex_unavailable',
                    message:
                        'No se pudo iniciar Codex. Verificá Node.js y el CLI de Codex.',
                },
            });
        });

        child.on('close', async (code) => {
            if (timedOut) {
                finish({
                    ok: false,
                    error: {
                        code: 'codex_timeout',
                        message:
                            'El análisis de Codex superó el tiempo máximo. No se guardó ningún cambio.',
                    },
                });

                return;
            }

            if (code !== 0) {
                const [errorCode, message] = classifyFailure(
                    stderr,
                    input.model,
                    input.effort,
                );

                finish({ ok: false, error: { code: errorCode, message } });

                return;
            }

            try {
                const rawOutput = await readFile(outputPath, 'utf8');

                if (rawOutput.length > OUTPUT_LIMIT_BYTES) {
                    finish({
                        ok: false,
                        error: {
                            code: 'codex_schema',
                            message:
                                'La respuesta estructurada de Codex es demasiado grande. No se guardó ningún cambio.',
                        },
                    });

                    return;
                }

                const proposal = JSON.parse(rawOutput);

                if (
                    typeof proposal !== 'object' ||
                    proposal === null ||
                    Array.isArray(proposal)
                ) {
                    finish({
                        ok: false,
                        error: {
                            code: 'codex_schema',
                            message:
                                'Codex no devolvió una propuesta estructurada. No se guardó ningún cambio.',
                        },
                    });

                    return;
                }

                finish({ ok: true, proposal });
            } catch {
                finish({
                    ok: false,
                    error: {
                        code: 'codex_schema',
                        message:
                            'Codex no devolvió una propuesta JSON válida. No se guardó ningún cambio.',
                    },
                });
            }
        });

        child.stdin.end(input.prompt, 'utf8');
    });
}

async function main() {
    let input;

    try {
        input = JSON.parse(await readStdin());
    } catch {
        failure(
            'codex_request',
            'La solicitud al proceso de Codex no es válida.',
        );
        return;
    }

    const validationError = validateInput(input);

    if (validationError !== null) {
        failure('codex_request', validationError);
        return;
    }

    let workDirectory;

    try {
        workDirectory = await mkdtemp(path.join(tmpdir(), 'cv-adaptation-'));
        const result = await runCodex(input, workDirectory);
        writeResult(result);
    } catch {
        failure(
            'codex_unavailable',
            'No se pudo preparar el proceso local de Codex. No se guardó ningún cambio.',
        );
    } finally {
        if (workDirectory) {
            await rm(workDirectory, { recursive: true, force: true }).catch(
                () => {},
            );
        }
    }
}

main().catch(() => {
    failure(
        'codex_failed',
        'Ocurrió un error inesperado al ejecutar Codex. No se guardó ningún cambio.',
    );
});
