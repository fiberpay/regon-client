<?php

namespace Fiberpay\RegonClient;

/**
 * Registry responses contain arrays and scalar values, without XML objects.
 * Implementations propagate lookup and transport failures to the caller.
 */
interface RegonClientInterface
{
    public function findByRegon(string $regon, string $language = "pl"): array;

    public function findByNip(string $nip, string $language = "pl"): array;

    public function findByKrs(string $krs, string $language = "pl"): array;

    public function getCumulativeReport(
        string $date,
        string $collectiveReportType,
        string $language = "pl",
    ): array;

    public function getReport(string $regon, string $reportType, string $language = "pl"): array;
}
