<?php
class DeliveryHelper
{
    public static function districtList()
    {
        return [
            'Colombo 01-15',
            'Colombo Other',
            'Gampaha',
            'Kalutara',
            'Kandy',
            'Matale',
            'Nuwara Eliya',
            'Galle',
            'Matara',
            'Hambantota',
            'Jaffna',
            'Kilinochchi',
            'Mannar',
            'Vavuniya',
            'Mullaitivu',
            'Batticaloa',
            'Ampara',
            'Trincomalee',
            'Kurunegala',
            'Puttalam',
            'Anuradhapura',
            'Polonnaruwa',
            'Badulla',
            'Monaragala',
            'Ratnapura',
            'Kegalle'
        ];
    }

    public static function subtotal(array $items)
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $price = (float) ($item['price'] ?? 0);
            $subtotal += ($price * $qty);
        }

        return $subtotal;
    }

    public static function calculateShipping(array $items, $district, array $settings, array $ratesMap)
    {
        $district = trim((string) $district);
        $chargeableWeight = 0;
        $chargeableCount = 0;

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $isFreeShipping = !empty($item['is_free_shipping']);
            $weightGrams = max(0, (int) ($item['weight_grams'] ?? 0));

            if ($isFreeShipping || $weightGrams <= 0) {
                continue;
            }

            $chargeableCount += $qty;
            $chargeableWeight += ($weightGrams * $qty);
        }

        $subtotal = self::subtotal($items);
        $result = [
            'subtotal' => $subtotal,
            'shipping_fee' => 0.0,
            'total' => $subtotal,
            'chargeable_weight_grams' => $chargeableWeight,
            'chargeable_items_count' => $chargeableCount,
            'district' => $district,
            'district_label' => $district,
            'first_kg_price' => 0.0,
            'additional_kg_price' => 0.0,
            'uses_global_rate' => !empty($settings['delivery_apply_all_districts']),
            'requires_district' => empty($settings['delivery_apply_all_districts']),
            'has_rate' => false
        ];

        if ($chargeableWeight <= 0) {
            $result['has_rate'] = true;
            return $result;
        }

        $firstKg = (float) ($settings['delivery_all_first_kg'] ?? 0);
        $additionalKg = (float) ($settings['delivery_all_additional_kg'] ?? 0);

        if (empty($settings['delivery_apply_all_districts'])) {
            if ($district === '' || empty($ratesMap[$district])) {
                return $result;
            }

            $firstKg = (float) ($ratesMap[$district]['first_kg_price'] ?? 0);
            $additionalKg = (float) ($ratesMap[$district]['additional_kg_price'] ?? 0);
        } elseif ($district === '') {
            $district = 'All Districts';
        }

        $result['district'] = $district;
        $result['district_label'] = $district;
        $result['first_kg_price'] = $firstKg;
        $result['additional_kg_price'] = $additionalKg;
        $result['has_rate'] = true;

        if ($chargeableWeight <= 1000) {
            $shippingFee = $firstKg;
        } else {
            $extraWeight = $chargeableWeight - 1000;
            $extraBlocks = (int) ceil($extraWeight / 1000);
            $shippingFee = $firstKg + ($extraBlocks * $additionalKg);
        }

        $result['shipping_fee'] = $shippingFee;
        $result['total'] = $subtotal + $shippingFee;

        return $result;
    }
}
?>
