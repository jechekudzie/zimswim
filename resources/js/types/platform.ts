export type OrganizationType = 'national' | 'province' | 'club';

export type OrganizationSummary = {
    id: number;
    name: string;
    slug: string;
    type: OrganizationType;
    type_label: string;
};

export type Option<T = string> = {
    value: T;
    label: string;
};

export type Flash = {
    success: string | null;
    error: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type SeasonSummary = {
    name: string;
    age_up_date?: string;
    starts_on?: string;
    ends_on?: string;
};

/**
 * How the public reaches the national body. Shared on every page from the
 * national organisation's own record, so a missing number is a null here
 * rather than a placeholder on the page.
 */
export type Federation = {
    name: string;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
};
