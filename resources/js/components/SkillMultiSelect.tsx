import { Checkbox } from '@/components/ui/checkbox';
import {
    SKILL_CATEGORY_LABELS,
    SKILL_CATEGORY_ORDER,
} from '@/lib/skill-categories';

export type SkillOption = {
    id: number;
    name: string;
    category: string;
};

type SkillMultiSelectProps = {
    skills: SkillOption[];
    selected: number[];
    onChange: (ids: number[]) => void;
    error?: string;
};

export default function SkillMultiSelect({
    skills,
    selected,
    onChange,
    error,
}: SkillMultiSelectProps) {
    if (skills.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Todavía no hay habilidades en el catálogo. Podés agregarlas
                desde la sección Habilidades.
            </p>
        );
    }

    const toggle = (id: number, checked: boolean) => {
        if (checked) {
            onChange([...selected, id]);
        } else {
            onChange(selected.filter((selectedId) => selectedId !== id));
        }
    };

    const groups = SKILL_CATEGORY_ORDER.map((category) => ({
        category,
        items: skills.filter((skill) => skill.category === category),
    })).filter((group) => group.items.length > 0);

    return (
        <div className="space-y-3">
            {groups.map((group) => (
                <fieldset key={group.category}>
                    <legend className="text-sm font-medium">
                        {SKILL_CATEGORY_LABELS[group.category] ??
                            group.category}
                    </legend>
                    <div className="mt-1 grid gap-2 sm:grid-cols-2">
                        {group.items.map((skill) => (
                            <label
                                key={skill.id}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={selected.includes(skill.id)}
                                    onCheckedChange={(checked) =>
                                        toggle(skill.id, checked === true)
                                    }
                                />
                                {skill.name}
                            </label>
                        ))}
                    </div>
                </fieldset>
            ))}
            {error ? <p className="text-sm text-destructive">{error}</p> : null}
        </div>
    );
}
