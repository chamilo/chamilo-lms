<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use const DNS_A;
use const DNS_AAAA;

final class PhpDnsHostResolver implements DnsHostResolver
{
    public function resolve(string $host): array
    {
        $ips = [];

        $flags = 0;
        if (\defined('DNS_A')) {
            $flags |= DNS_A;
        }
        if (\defined('DNS_AAAA')) {
            $flags |= DNS_AAAA;
        }

        $records = false;
        if ($flags > 0) {
            $records = @dns_get_record($host, $flags);
        }

        if (\is_array($records)) {
            foreach ($records as $record) {
                if (!\is_array($record)) {
                    continue;
                }
                if (isset($record['ip']) && \is_string($record['ip']) && '' !== $record['ip']) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6']) && \is_string($record['ipv6']) && '' !== $record['ipv6']) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ([] === $ips) {
            $ipv4 = @gethostbynamel($host);
            if (\is_array($ipv4)) {
                foreach ($ipv4 as $ip) {
                    if (\is_string($ip) && '' !== $ip) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
