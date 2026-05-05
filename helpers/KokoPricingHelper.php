<?php

class KokoPricingHelper
{
    public static function getHandlingFeePercentage(array $settings)
    {
        return max(0, (float) ($settings['koko_handling_fee_percentage'] ?? 0));
    }

    public static function isEnabled(array $settings)
    {
        return !empty($settings['koko_enabled']);
    }

    public static function getEffectiveProductPrice(array $product)
    {
        $regularPrice = (float) ($product['price'] ?? 0);
        $salePrice = isset($product['sale_price']) ? (float) $product['sale_price'] : null;

        if ($salePrice !== null && $salePrice > 0 && $salePrice < $regularPrice) {
            return $salePrice;
        }

        return $regularPrice;
    }

    public static function getInstallmentData($baseAmount, array $settings)
    {
        $baseAmount = max(0, (float) $baseAmount);
        $feePercentage = self::getHandlingFeePercentage($settings);
        $totalAmount = $baseAmount;

        if ($feePercentage > 0) {
            $totalAmount += $baseAmount * ($feePercentage / 100);
        }

        return [
            'base_amount' => $baseAmount,
            'fee_percentage' => $feePercentage,
            'total_amount' => $totalAmount,
            'installment_amount' => $totalAmount / 3
        ];
    }
}
