import type { ReactNode } from 'react';

import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option } from '@/types';

export function Field({
    label,
    htmlFor,
    hint,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    hint?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export function TextField({
    label,
    name,
    defaultValue,
    type = 'text',
    hint,
    error,
    required,
    placeholder,
    step,
}: {
    label: string;
    name: string;
    defaultValue?: string | number | null;
    type?: string;
    hint?: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
    step?: string;
}) {
    return (
        <Field label={label} htmlFor={name} hint={hint} error={error}>
            <Input
                id={name}
                name={name}
                type={type}
                step={step}
                required={required}
                placeholder={placeholder}
                defaultValue={defaultValue ?? ''}
            />
        </Field>
    );
}

export function SelectField<T extends string | number>({
    label,
    name,
    options,
    defaultValue,
    hint,
    error,
    placeholder,
}: {
    label: string;
    name: string;
    options: Option<T>[];
    defaultValue?: T | null;
    hint?: string;
    error?: string;
    placeholder?: string;
}) {
    return (
        <Field label={label} htmlFor={name} hint={hint} error={error}>
            <select
                id={name}
                name={name}
                defaultValue={defaultValue ?? ''}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                {placeholder && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Field>
    );
}

export function TextareaField({
    label,
    name,
    defaultValue,
    rows = 4,
    hint,
    error,
}: {
    label: string;
    name: string;
    defaultValue?: string | null;
    rows?: number;
    hint?: string;
    error?: string;
}) {
    return (
        <Field label={label} htmlFor={name} hint={hint} error={error}>
            <textarea
                id={name}
                name={name}
                rows={rows}
                defaultValue={defaultValue ?? ''}
                className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            />
        </Field>
    );
}

export function CheckboxField({
    label,
    name,
    defaultChecked,
    description,
}: {
    label: string;
    name: string;
    defaultChecked?: boolean;
    description?: string;
}) {
    return (
        <label className="flex items-start gap-3">
            <Checkbox
                name={name}
                value="1"
                defaultChecked={defaultChecked ?? false}
            />
            <span className="text-sm">
                {label}
                {description && (
                    <span className="block text-xs text-muted-foreground">
                        {description}
                    </span>
                )}
            </span>
        </label>
    );
}
