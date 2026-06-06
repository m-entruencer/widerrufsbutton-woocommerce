<?php
/**
 * Datenzugriff auf die Custom Table entruencer_withdrawals.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Repository;

use Entruencer\Widerruf\Install\Migrator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CRUD auf der Widerrufs-Tabelle. Ausschliesslich Prepared Statements.
 */
final class WithdrawalRepository
{
    /**
     * Erlaubte Status-Werte (Whitelist).
     */
    public const STATUSES = ['eingegangen', 'in_bearbeitung', 'erledigt', 'abgelehnt'];

    /**
     * Spalten, die ueber insert()/update() beschrieben werden duerfen,
     * mit ihrem wpdb-Format. Schuetzt vor Schreibzugriff auf Fremdspalten.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'order_id'               => '%d',
        'order_number'           => '%s',
        'customer_email'         => '%s',
        'customer_name'          => '%s',
        'received_at_utc'        => '%s',
        'received_at_local'      => '%s',
        'case_type'              => '%s',
        'deadline_days_snapshot' => '%d',
        'order_date_snapshot'    => '%s',
        'excluded_flag'          => '%d',
        'exclusion_reason'       => '%s',
        'waiver_proven'          => '%d',
        'confirmation_mail_sent' => '%d',
        'status'                 => '%s',
        'created_at'             => '%s',
    ];

    /**
     * Voller Tabellenname inkl. wpdb-Prefix.
     */
    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . Migrator::TABLE;
    }

    /**
     * Reduziert ein Datenarray auf erlaubte Spalten und liefert
     * parallel das passende Format-Array fuer $wpdb.
     *
     * @param array<string, mixed> $data
     *
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function filter_columns(array $data): array
    {
        $clean   = [];
        $formats = [];

        foreach (self::COLUMNS as $column => $format) {
            if (array_key_exists($column, $data)) {
                $clean[$column]  = $data[$column];
                $formats[]       = $format;
            }
        }

        return [$clean, $formats];
    }

    /**
     * Legt einen Widerruf-Datensatz an.
     *
     * @param array<string, mixed> $data Spalten -> Werte.
     *
     * @return int|null Neue ID oder null bei Fehler.
     */
    public function insert(array $data): ?int
    {
        global $wpdb;

        if (empty($data['created_at'])) {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
        }

        [$clean, $formats] = $this->filter_columns($data);

        if ($clean === []) {
            return null;
        }

        $result = $wpdb->insert($this->table(), $clean, $formats);

        if ($result === false) {
            return null;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Liefert einen Datensatz per ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $this->table() . ' WHERE id = %d',
                $id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Listet Datensaetze (gefiltert/paginiert).
     *
     * @param array<string, mixed> $args status, search, limit, offset.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(array $args = []): array
    {
        global $wpdb;

        [$where, $params] = $this->build_where($args);

        $limit  = isset($args['limit']) ? max(1, (int) $args['limit']) : 20;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

        $sql      = 'SELECT * FROM ' . $this->table() . $where
            . ' ORDER BY id DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Zaehlt Datensaetze (fuer Pagination), gleiche Filter wie list().
     *
     * @param array<string, mixed> $args
     */
    public function count(array $args = []): int
    {
        global $wpdb;

        [$where, $params] = $this->build_where($args);

        $sql = 'SELECT COUNT(*) FROM ' . $this->table() . $where;

        if ($params === []) {
            return (int) $wpdb->get_var($sql);
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Baut WHERE-Klausel + Parameter aus Filter-Args.
     *
     * @param array<string, mixed> $args
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function build_where(array $args): array
    {
        global $wpdb;

        $clauses = [];
        $params  = [];

        if (!empty($args['status']) && in_array($args['status'], self::STATUSES, true)) {
            $clauses[] = 'status = %s';
            $params[]  = $args['status'];
        }

        if (!empty($args['search'])) {
            $search    = '%' . $wpdb->esc_like((string) $args['search']) . '%';
            $clauses[] = '(order_number LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)';
            $params[]  = $search;
            $params[]  = $search;
            $params[]  = $search;
        }

        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);

        return [$where, $params];
    }

    /**
     * Aktualisiert beliebige (whitelisted) Spalten eines Datensatzes.
     *
     * @param int                  $id   Datensatz-ID.
     * @param array<string, mixed> $data Spalten -> Werte.
     *
     * @return bool Erfolg.
     */
    public function update(int $id, array $data): bool
    {
        global $wpdb;

        if (isset($data['status']) && !in_array($data['status'], self::STATUSES, true)) {
            return false;
        }

        [$clean, $formats] = $this->filter_columns($data);

        if ($clean === []) {
            return false;
        }

        $result = $wpdb->update($this->table(), $clean, ['id' => $id], $formats, ['%d']);

        return $result !== false;
    }

    /**
     * Aktualisiert den Status eines Datensatzes.
     *
     * @param int    $id     Datensatz-ID.
     * @param string $status Neuer Status (Whitelist).
     *
     * @return bool Erfolg.
     */
    public function update_status(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        return $this->update($id, ['status' => $status]);
    }
}
