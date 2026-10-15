import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    SKILL_CATEGORY_LABELS,
    SKILL_CATEGORY_ORDER,
} from '@/lib/skill-categories';

export type SkillFormRecord = {
    id: number;
    name: string;
    category: string;
};

export default function SkillForm({ record }: { record?: SkillFormRecord }) {
    const isEdit = record !== undefined;

    const { data, setData, post, put, processing, errors } = useForm({
        name: record?.name ?? '',
        category: record?.category ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(`/skills/${record.id}`);
        } else {
            post('/skills');
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Datos de la habilidad</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="name">Nombre *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            aria-invalid={Boolean(errors.name)}
                        />
                        {errors.name ? (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="category">Categoría *</Label>
                        <select
                            id="category"
                            value={data.category}
                            onChange={(event) =>
                                setData('category', event.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            aria-invalid={Boolean(errors.category)}
                        >
                            <option value="">Seleccionar categoría</option>
                            {SKILL_CATEGORY_ORDER.map((category) => (
                                <option key={category} value={category}>
                                    {SKILL_CATEGORY_LABELS[category]}
                                </option>
                            ))}
                        </select>
                        {errors.category ? (
                            <p className="text-sm text-destructive">
                                {errors.category}
                            </p>
                        ) : null}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" asChild>
                    <Link href="/skills">Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {isEdit ? 'Guardar cambios' : 'Crear habilidad'}
                </Button>
            </div>
        </form>
    );
}
