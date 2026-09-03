<?php
/**
 * Calculate the distance between two geographical coordinates in Kilometers.
 * Uses the Haversine formula.
 *
 * @param float $lat1 Latitude of point 1
 * @param float $lon1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lon2 Longitude of point 2
 * @return float Distance in kilometers
 */
function calculateDistanceInKm($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the earth in km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance, 2); // Return distance rounded to 2 decimal places
}

/**
 * Calculate Delivery Fee based on distance.
 * Adjust these constants to match your campus pricing model.
 */
function calculateDeliveryFee($distanceInKm) {
    $BASE_FEE = 100.00;      // Minimum base delivery fee (₦)
    $PER_KM_RATE = 50.00;    // Additional charge per kilometer (₦)
    $MAX_FEE = 1000.00;      // Cap the maximum delivery fee (₦)
    
    $calculated_fee = $BASE_FEE + ($distanceInKm * $PER_KM_RATE);
    
    // Ensure fee doesn't exceed maximum, and isn't less than base
    return min(max($calculated_fee, $BASE_FEE), $MAX_FEE);
}
?>