<?php

namespace App\Services;

class GroqEmergencyAiService
{
    public function summarize(array $profile): array
    {
        $normalized = $this->normalizeProfile($profile);

        $summaryParts = [];
        $alerts = [];

        if ($normalized['blood_type']) {
            $summaryParts[] = 'Blood type ' . $normalized['blood_type'] . '.';
        } else {
            $alerts[] = 'Blood type not listed';
        }

        if (!empty($normalized['allergies'])) {
            $summaryParts[] = 'Listed allergies: ' . implode(', ', $normalized['allergies']) . '.';
        } else {
            $summaryParts[] = 'No listed allergies.';
        }

        if (!empty($normalized['medical_conditions'])) {
            $summaryParts[] = 'Listed conditions: ' . implode(', ', $normalized['medical_conditions']) . '.';
        } else {
            $summaryParts[] = 'No listed conditions.';
        }

        if ($normalized['current_medications']) {
            if ($this->looksSuspicious($normalized['current_medications'])) {
                $summaryParts[] = 'Medication field contains suspicious content and should be verified.';
                $alerts[] = 'Medication field may contain invalid data';
            } else {
                $summaryParts[] = 'Current medications listed.';
            }
        } else {
            $summaryParts[] = 'No medications listed.';
        }

        if ($normalized['primary_contact']) {
            $summaryParts[] = 'Primary contact available.';
        } else {
            $alerts[] = 'Primary contact not listed';
        }

        if (
            !$normalized['blood_type'] ||
            empty($normalized['allergies']) ||
            empty($normalized['medical_conditions']) ||
            !$normalized['current_medications']
        ) {
            $alerts[] = 'Some medical information not listed';
        }

        $summary = implode(' ', $summaryParts);
        $alerts = array_values(array_unique(array_filter($alerts)));

        if (empty($alerts)) {
            $alerts[] = 'No critical AI alerts detected';
        }

        return [
            'summary' => $summaryParts,
            'alerts' => $alerts,
        ];
    }

    protected function normalizeProfile(array $profile): array
    {
        $allergies = $profile['allergies'] ?? [];
        $conditions = $profile['medical_conditions'] ?? [];
        $medications = $profile['current_medications'] ?? null;
        $contacts = $profile['emergency_contacts'] ?? [];

        if (is_string($allergies)) {
            $allergies = array_filter(array_map('trim', explode(',', $allergies)));
        }

        if (is_string($conditions)) {
            $conditions = array_filter(array_map('trim', explode(',', $conditions)));
        }

        if (is_array($medications)) {
            $medications = implode(', ', array_filter(array_map('trim', $medications)));
        }

        $primaryContact = null;

        if (is_array($contacts)) {
            foreach ($contacts as $contact) {
                if (!empty($contact['is_primary'])) {
                    $primaryContact = $contact;
                    break;
                }
            }

            if (!$primaryContact && !empty($contacts[0])) {
                $primaryContact = $contacts[0];
            }
        }

        return [
            'blood_type' => $this->cleanString($profile['blood_type'] ?? null),
            'allergies' => array_values(array_filter(array_map([$this, 'cleanString'], (array) $allergies))),
            'medical_conditions' => array_values(array_filter(array_map([$this, 'cleanString'], (array) $conditions))),
            'current_medications' => $this->cleanString($medications),
            'primary_contact' => $primaryContact,
        ];
    }

    protected function cleanString($value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function looksSuspicious(?string $text): bool
    {
        if (!$text) {
            return false;
        }

        $patterns = [
            '/select\s+.*\s+from\s+/i',
            '/insert\s+into\s+/i',
            '/update\s+.*\s+set\s+/i',
            '/delete\s+from\s+/i',
            '/where\s+.+\=\s*\?/i',
            '/<script\b/i',
            '/http(s)?:\/\//i',
            '/password/i',
            '/username/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}