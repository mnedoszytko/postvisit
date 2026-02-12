/** Patient condition from the API */
export interface Condition {
    id: string;
    code: string;
    code_display: string;
    clinical_status: string | null;
    clinical_notes: string | null;
}

/** Medication linked to a prescription */
export interface Medication {
    id: string;
    generic_name: string;
    display_name: string;
    brand_names: string[] | null;
    rxnorm_code: string | null;
}

/** Prescription record from the API */
export interface Prescription {
    id: string;
    dose_quantity: string;
    dose_unit: string;
    frequency: string;
    frequency_text: string | null;
    route: string | null;
    special_instructions: string | null;
    indication: string | null;
    status: string;
    medication: Medication | null;
}

/** Clinical reference from the API */
export interface Reference {
    id: string;
    title: string;
    authors: string | null;
    journal: string | null;
    year: number | null;
    doi: string | null;
    url: string | null;
    pmid: string | null;
    summary: string | null;
    category: string | null;
    verified: boolean;
}

/** Condition lookup result */
export interface ConditionMatch {
    code: string;
    name: string;
}

/** OpenEvidence mock source */
export interface OeSource {
    title: string;
    journal: string;
    year: string;
    type: string;
}

/** OpenEvidence mock result */
export interface OeResult {
    answer: string;
    confidence: number;
    evidenceLevel: string;
    sources: OeSource[];
}

/** Drug label result from DailyMed */
export interface DrugLabelResult {
    title: string;
    author: string | null;
    version_number: string | null;
    setid: string | null;
}

/** Search result item from NIH/NLM/HCPCS */
export interface SearchResultItem {
    code?: string;
    name?: string;
    title?: string;
    extra?: string;
}
