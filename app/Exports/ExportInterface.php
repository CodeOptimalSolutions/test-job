<?php

namespace DTApi\Exports;

interface ExportInterface
{
    public function getData($id, $type);

    public function getInvoicesData($id);

    public function exportSalaries($data);

    public function exportInvoices($data);
}