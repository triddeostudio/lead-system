ALTER TABLE leads
ADD COLUMN IF NOT EXISTS client_website TEXT;

CREATE INDEX IF NOT EXISTS idx_leads_client_website
ON leads(client_website);
