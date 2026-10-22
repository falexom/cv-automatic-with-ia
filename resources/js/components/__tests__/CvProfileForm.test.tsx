import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { describe, expect, it } from 'vitest';
import CvProfileForm from '@/components/forms/CvProfileForm';
import {
    emptyCvProfileFormData,
    type CvProfileFormData,
    type CvProfileOptions,
} from '@/lib/cv-profiles';

const options: CvProfileOptions = {
    work_experiences: [
        {
            id: 1,
            company: 'Acme',
            position: 'Analista',
            start_year: 2022,
            start_month: 3,
            end_year: null,
            end_month: null,
            is_current: true,
        },
    ],
    education_entries: [
        {
            id: 2,
            institution: 'Universidad',
            program: 'Sistemas',
            degree_level: 'Licenciatura',
            start_year: 2015,
            start_month: 2,
            end_year: 2020,
            end_month: 12,
            is_in_progress: false,
        },
    ],
    certifications: [
        {
            id: 3,
            name: 'Curso de Laravel',
            institution: 'Academy',
            date_year: 2023,
            date_month: 6,
        },
    ],
    skills: [{ id: 4, name: 'PHP', category: 'tecnologia' }],
    projects: [
        {
            id: 5,
            name: 'Portal',
            role: 'Desarrolladora',
            start_year: 2021,
            start_month: 1,
            end_year: 2021,
            end_month: 9,
        },
    ],
};

const emptyOptions: CvProfileOptions = {
    work_experiences: [],
    education_entries: [],
    certifications: [],
    skills: [],
    projects: [],
};

function Harness({
    initial,
    formOptions = options,
    errors = {},
}: {
    initial?: CvProfileFormData;
    formOptions?: CvProfileOptions;
    errors?: Record<string, string>;
}) {
    const [data, setData] = useState(initial ?? emptyCvProfileFormData());

    return (
        <CvProfileForm
            data={data}
            options={formOptions}
            errors={errors}
            processing={false}
            submitLabel="Guardar"
            cancelHref="/profiles"
            onChange={setData}
            onSubmit={(event) => event.preventDefault()}
        />
    );
}

describe('CvProfileForm', () => {
    it('renders the five content sections and preselects nothing on a new profile', () => {
        render(<Harness />);

        expect(screen.getByText('Experiencia laboral')).toBeInTheDocument();
        expect(screen.getByText('Educación')).toBeInTheDocument();
        expect(
            screen.getByText('Cursos y certificaciones'),
        ).toBeInTheDocument();
        expect(screen.getByText('Habilidades')).toBeInTheDocument();
        expect(screen.getByText('Proyectos')).toBeInTheDocument();

        expect(
            screen.getByRole('checkbox', { name: /Analista — Acme/ }),
        ).not.toBeChecked();
        expect(
            screen.getByRole('checkbox', { name: /Sistemas — Universidad/ }),
        ).not.toBeChecked();
        expect(
            screen.queryByLabelText(/^Relevancia de /),
        ).not.toBeInTheDocument();
    });

    it('adds the relevance selector with Media by default and removes its item', () => {
        render(<Harness />);

        const checkbox = screen.getByRole('checkbox', {
            name: /Analista — Acme/,
        });

        fireEvent.click(checkbox);

        const select = screen.getByLabelText('Relevancia de Analista — Acme');
        expect(select).toHaveValue('media');

        fireEvent.change(select, { target: { value: 'alta' } });
        expect(select).toHaveValue('alta');

        fireEvent.click(checkbox);

        expect(
            screen.queryByLabelText('Relevancia de Analista — Acme'),
        ).not.toBeInTheDocument();
    });

    it('guides to the master section when a class has no options', () => {
        render(<Harness formOptions={emptyOptions} />);

        expect(
            screen.getByText('No hay habilidades en el Perfil Maestro.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Ir a Habilidades' }),
        ).toHaveAttribute('href', '/skills');
    });

    it('shows received errors next to the field and the selected element', () => {
        const initial = emptyCvProfileFormData();
        initial.work_experiences = [{ id: 1, relevance: 'media' }];

        render(
            <Harness
                initial={initial}
                errors={{
                    name: 'El nombre del perfil es obligatorio.',
                    'work_experiences.0.id':
                        'El elemento seleccionado no pertenece al Perfil Maestro o ya no existe.',
                }}
            />,
        );

        expect(
            screen.getByText('El nombre del perfil es obligatorio.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'El elemento seleccionado no pertenece al Perfil Maestro o ya no existe.',
            ),
        ).toBeInTheDocument();
    });

    it('keeps the written values while editing', () => {
        render(<Harness />);

        const name = screen.getByLabelText('Nombre del perfil *');
        fireEvent.change(name, { target: { value: 'Perfil con acentos: ñ' } });

        expect(name).toHaveValue('Perfil con acentos: ñ');
    });

    it('offers no delete, drag or manual reorder controls', () => {
        render(<Harness />);

        expect(screen.queryByText(/Eliminar/i)).not.toBeInTheDocument();
        expect(screen.queryByText(/Arrastrar/i)).not.toBeInTheDocument();
        expect(screen.queryByText(/Reordenar/i)).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Eliminar/i }),
        ).not.toBeInTheDocument();
    });
});
