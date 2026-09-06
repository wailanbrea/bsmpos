export interface SystemModule {
    code: string;
    name: string;
    description: string;
    category: string;
    is_core: boolean;
    is_enabled: boolean;
}

export interface ModuleIndexResponse {
    modules: SystemModule[];
    enabled: string[];
    business_type: boolean;
    business_type_code?: string | null;
}

export interface BusinessType {
    code: string;
    name: string;
    description: string;
    icon: string;
}

export interface PresetModule {
    code: string;
    name: string;
    category: string;
    is_core: boolean;
    enabled_by_default: boolean;
    is_recommended: boolean;
}

export interface BusinessTypesResponse {
    business_types: BusinessType[];
    modules?: PresetModule[];
}
