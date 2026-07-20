<?php

namespace App\Services;

use App\Models\Constituent;
use App\Models\FormSubmission;
use App\Models\ServiceRequest;

/**
 * Attaches new public-site intake to a constituent record.
 *
 * Called after the intake row is already saved, and every method is wrapped by
 * the caller in rescue(): a resident's pothole report must never fail because
 * the CRM side of the house had a bad day.
 */
class ConstituentIntake
{
    /** Link a freshly filed service request to its resident. */
    public static function fromServiceRequest(ServiceRequest $request): ?Constituent
    {
        // Anonymous means anonymous. No record, no linkage, nothing retained.
        if ($request->is_anonymous) {
            return null;
        }

        $constituent = Constituent::resolve([
            'name' => $request->reporter_name,
            'email' => $request->reporter_email,
            'phone' => $request->reporter_phone,
            'address_line1' => $request->location_text,
        ], 'service_request');

        if ($constituent) {
            $request->forceFill(['constituent_id' => $constituent->id])->save();
            $constituent->touchInteraction($request->created_at);
        }

        return $constituent;
    }

    /** Link a freshly filed form submission to its resident. */
    public static function fromFormSubmission(FormSubmission $submission): ?Constituent
    {
        $constituent = Constituent::resolve(self::harvest((array) $submission->data), 'form_submission');

        if ($constituent) {
            $submission->forceFill(['constituent_id' => $constituent->id])->save();
            $constituent->touchInteraction($submission->created_at);
        }

        return $constituent;
    }

    /**
     * Pull contact details out of an author-defined form payload.
     *
     * Form field keys are whatever the clerk who built the form typed, so this
     * matches on key names and falls back to detecting an email-shaped value.
     * Ambiguous fields are skipped rather than guessed: a wrong guess welds two
     * residents into one record, which is far worse than an unlinked row.
     */
    public static function harvest(array $data): array
    {
        $out = [
            'name' => null, 'email' => null, 'phone' => null,
            'address_line1' => null, 'city' => null, 'state' => null, 'postal_code' => null,
        ];

        foreach ($data as $key => $value) {
            if (is_array($value) || $value === null || trim((string) $value) === '') {
                continue;
            }
            $value = trim((string) $value);
            $k = strtolower((string) $key);

            if (! $out['email'] && (str_contains($k, 'email') || str_contains($k, 'e_mail'))) {
                $out['email'] = $value;
            } elseif (! $out['email'] && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $out['email'] = $value;
            }

            if (! $out['phone'] && (str_contains($k, 'phone') || str_contains($k, 'mobile') || str_contains($k, 'telephone'))) {
                $out['phone'] = $value;
            }

            if (! $out['name'] && str_contains($k, 'name') && ! str_contains($k, 'user') && ! str_contains($k, 'business')) {
                $out['name'] = $value;
            }

            if (! $out['address_line1'] && (str_contains($k, 'address') || str_contains($k, 'street'))) {
                $out['address_line1'] = $value;
            }
            if (! $out['city'] && $k === 'city') {
                $out['city'] = $value;
            }
            if (! $out['state'] && $k === 'state') {
                $out['state'] = $value;
            }
            if (! $out['postal_code'] && (str_contains($k, 'zip') || str_contains($k, 'postal'))) {
                $out['postal_code'] = $value;
            }
        }

        return $out;
    }
}
