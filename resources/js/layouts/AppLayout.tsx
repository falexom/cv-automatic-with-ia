import { Link, usePage } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import type { AppLayoutProps } from '@/types';

const navigation = [
    { label: 'Perfil', href: '/profile' },
    { label: 'Experiencia', href: '/experiences' },
    { label: 'Educación', href: '/education' },
    { label: 'Cursos y certificaciones', href: '/certifications' },
    { label: 'Habilidades', href: '/skills' },
    { label: 'Proyectos', href: '/projects' },
    { label: 'Documentos', href: '/documents' },
    { label: 'Perfiles', href: '/profiles' },
];

export default function AppLayout({ children, title }: AppLayoutProps) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { name } = usePage().props;

    useFlashToast();

    return (
        <div className="min-h-screen bg-muted/40">
            <header className="border-b bg-background">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                    <Link
                        href="/profile"
                        className="text-base font-semibold tracking-tight"
                    >
                        {name}
                    </Link>
                    <nav
                        aria-label="Secciones del perfil"
                        className="flex flex-wrap items-center gap-1"
                    >
                        {navigation.map((item) => {
                            const isActive = isCurrentOrParentUrl(item.href);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-sm transition-colors',
                                        isActive
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                                    )}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </header>
            <main className="mx-auto w-full max-w-6xl px-4 py-6">
                {title ? (
                    <h1 className="mb-6 text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                ) : null}
                {children}
            </main>
        </div>
    );
}
