<?php

declare(strict_types=1);

final class LeadRepository
{
    public function create(array $lead): string
    {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO leads (
                source_site, source_url, form_name,
                name, email, phone, company, message,
                utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                referrer, ip_address, user_agent,
                status, priority, consent, spam_score, raw_payload
            ) VALUES (
                :source_site, :source_url, :form_name,
                :name, :email, :phone, :company, :message,
                :utm_source, :utm_medium, :utm_campaign, :utm_term, :utm_content,
                :referrer, :ip_address, :user_agent,
                :status, :priority, :consent, :spam_score, :raw_payload
            ) RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'source_site' => $lead['source_site'] ?? null,
            'source_url' => $lead['source_url'] ?? null,
            'form_name' => $lead['form_name'] ?? null,
            'name' => $lead['name'] ?? null,
            'email' => $lead['email'] ?? null,
            'phone' => $lead['phone'] ?? null,
            'company' => $lead['company'] ?? null,
            'message' => $lead['message'] ?? null,
            'utm_source' => $lead['utm_source'] ?? null,
            'utm_medium' => $lead['utm_medium'] ?? null,
            'utm_campaign' => $lead['utm_campaign'] ?? null,
            'utm_term' => $lead['utm_term'] ?? null,
            'utm_content' => $lead['utm_content'] ?? null,
            'referrer' => $lead['referrer'] ?? null,
            'ip_address' => $lead['ip_address'] ?? null,
            'user_agent' => $lead['user_agent'] ?? null,
            'status' => $lead['status'] ?? 'nuevo',
            'priority' => $lead['priority'] ?? 'media',
            'consent' => ($lead['consent'] ?? false) ? 1 : 0,
            'spam_score' => $lead['spam_score'] ?? 0,
            'raw_payload' => json_encode($lead['raw_payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $id = $stmt->fetchColumn();
        $this->addEvent((string) $id, 'lead_created', ['source_site' => $lead['source_site'] ?? null], 'system');

        return (string) $id;
    }

    public function list(array $filters = [], int $limit = 100): array
    {
        $pdo = Database::connection();
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['source_site'])) {
            $where[] = 'source_site ILIKE :source_site';
            $params['source_site'] = '%' . $filters['source_site'] . '%';
        }

        if (!empty($filters['q'])) {
            $where[] = '(name ILIKE :q OR email ILIKE :q OR phone ILIKE :q OR message ILIKE :q OR company ILIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT * FROM leads';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', max(1, min($limit, 500)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(string $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM leads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $lead = $stmt->fetch();

        return $lead ?: null;
    }

    public function events(string $leadId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM lead_events WHERE lead_id = :lead_id ORDER BY created_at DESC');
        $stmt->execute(['lead_id' => $leadId]);

        return $stmt->fetchAll();
    }

    public function update(string $id, array $data, string $createdBy = 'admin'): void
    {
        $before = $this->find($id);

        $stmt = Database::connection()->prepare('
            UPDATE leads
            SET status = :status,
                priority = :priority,
                assigned_to = :assigned_to,
                next_action_at = :next_action_at,
                notes = :notes
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?: null,
            'next_action_at' => $data['next_action_at'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);

        $this->addEvent($id, 'lead_updated', [
            'before' => [
                'status' => $before['status'] ?? null,
                'priority' => $before['priority'] ?? null,
            ],
            'after' => [
                'status' => $data['status'],
                'priority' => $data['priority'],
            ],
        ], $createdBy);
    }

    public function addEvent(string $leadId, string $type, array $data = [], ?string $createdBy = null): void
    {
        $stmt = Database::connection()->prepare('
            INSERT INTO lead_events (lead_id, event_type, event_data, created_by)
            VALUES (:lead_id, :event_type, :event_data, :created_by)
        ');

        $stmt->execute([
            'lead_id' => $leadId,
            'event_type' => $type,
            'event_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => $createdBy,
        ]);
    }
}
