SELECT purchasing.*
FROM
    purchasing
    INNER JOIN purchasing_detail ON purchasing.kodePurchasing = purchasing_detail.kodePurchasing
    LEFT JOIN vendor ON purchasing.kodeVendor = vendor.kodeVendor
WHERE
    purchasing.`kodePurchasing` = 'MUM/purchasing/1/000000001'