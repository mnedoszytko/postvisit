/** AI analysis structure returned by LibraryItemAnalyzer pipeline */
export interface AiAnalysisCategories {
    medical_topics: string[];
    evidence_level: string;
    evidence_description: string;
    specialty_areas: string[];
    document_type: string;
    icd10_codes: string[];
}

export interface AiAnalysisPatientRelevance {
    relevance_score: number;
    relevance_explanation: string;
    matching_conditions: string[];
    matching_medications: string[];
    actionable_insights: string[];
}

export interface AiAnalysisVerification {
    verified: boolean;
    issues: string[];
    confidence: string;
}

export interface AiAnalysis {
    title: string;
    summary: string;
    key_findings: string[];
    recommendations: string[];
    publication_info: Record<string, unknown>;
    categories: AiAnalysisCategories;
    patient_relevance: AiAnalysisPatientRelevance;
    verification: AiAnalysisVerification;
    pipeline_version: string;
    processed_at: string;
}

export type LibraryProcessingStatus =
    | 'pending'
    | 'extracting_text'
    | 'analyzing'
    | 'categorizing'
    | 'relating'
    | 'verifying'
    | 'completed'
    | 'failed';

export interface LibraryItem {
    id: string;
    title: string;
    source_type: 'pdf_upload' | 'url_scrape';
    source_url: string | null;
    processing_status: LibraryProcessingStatus;
    processing_error: string | null;
    ai_analysis: AiAnalysis | null;
    created_at: string;
    updated_at: string;
}

export interface PipelineStep {
    key: string;
    label: string;
}
