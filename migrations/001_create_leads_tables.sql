CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE IF NOT EXISTS leads (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    source_site TEXT,
    source_url TEXT,
    form_name TEXT,

    name TEXT,
    email TEXT,
    phone TEXT,
    company TEXT,
    message TEXT,

    utm_source TEXT,
    utm_medium TEXT,
    utm_campaign TEXT,
    utm_term TEXT,
    utm_content TEXT,

    referrer TEXT,
    ip_address INET,
    user_agent TEXT,

    status TEXT NOT NULL DEFAULT 'nuevo',
    priority TEXT NOT NULL DEFAULT 'media',

    assigned_to TEXT,
    next_action_at TIMESTAMPTZ,
    notes TEXT,

    consent BOOLEAN DEFAULT FALSE,
    spam_score INTEGER DEFAULT 0,

    raw_payload JSONB,

    CONSTRAINT leads_status_check CHECK (
        status IN (
            'nuevo',
            'contactado',
            'cualificado',
            'propuesta_enviada',
            'ganado',
            'perdido',
            'spam',
            'descartado'
        )
    ),

    CONSTRAINT leads_priority_check CHECK (
        priority IN (
            'baja',
            'media',
            'alta',
            'urgente'
        )
    )
);

CREATE TABLE IF NOT EXISTS lead_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    lead_id UUID NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    event_type TEXT NOT NULL,
    event_data JSONB,
    created_by TEXT
);

CREATE INDEX IF NOT EXISTS idx_leads_created_at ON leads(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);
CREATE INDEX IF NOT EXISTS idx_leads_priority ON leads(priority);
CREATE INDEX IF NOT EXISTS idx_leads_source_site ON leads(source_site);
CREATE INDEX IF NOT EXISTS idx_leads_email ON leads(email);
CREATE INDEX IF NOT EXISTS idx_leads_phone ON leads(phone);

CREATE INDEX IF NOT EXISTS idx_lead_events_lead_id ON lead_events(lead_id);
CREATE INDEX IF NOT EXISTS idx_lead_events_created_at ON lead_events(created_at DESC);

CREATE OR REPLACE FUNCTION update_leads_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_update_leads_updated_at ON leads;

CREATE TRIGGER trg_update_leads_updated_at
BEFORE UPDATE ON leads
FOR EACH ROW
EXECUTE FUNCTION update_leads_updated_at();
