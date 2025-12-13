<?php

namespace App\Services;

class ReportDefinitionsService
{
    /**
     * Get all report categories and their report types.
     */
    public function getCategories(): array
    {
        return [
            [
                'id' => 'income',
                'label' => 'דוחות הכנסות',
                'reports' => [
                    [
                        'id' => 'income_monthly',
                        'label' => 'דוח הכנסות חודשיות',
                        'category' => 'income',
                    ],
                ],
            ],
            [
                'id' => 'expenses',
                'label' => 'דוחות הוצאות',
                'reports' => [
                    [
                        'id' => 'expenses_monthly',
                        'label' => 'דוח הוצאות חודשיות',
                        'category' => 'expenses',
                    ],
                    [
                        'id' => 'expenses_high',
                        'label' => 'דוח הוצאות גבוהות (מעל 1000₪)',
                        'category' => 'expenses',
                    ],
                ],
            ],
            [
                'id' => 'donations',
                'label' => 'דוחות תרומות לפי תקופה / סוג תרומה',
                'reports' => [
                    [
                        'id' => 'donations_community',
                        'label' => 'תרומות מהקהילה',
                        'category' => 'donations',
                    ],
                    [
                        'id' => 'donations_external',
                        'label' => 'תרומות מחוץ לקהילה',
                        'category' => 'donations',
                    ],
                ],
            ],
            [
                'id' => 'debts',
                'label' => 'דוחות חובות וגבייה',
                'reports' => [
                    [
                        'id' => 'debts_open',
                        'label' => 'דוח חובות פתוחים',
                        'category' => 'debts',
                    ],
                    [
                        'id' => 'debts_by_type',
                        'label' => 'דוח חובות לפי סוג חוב',
                        'category' => 'debts',
                    ],
                    [
                        'id' => 'debts_by_debtor',
                        'label' => 'דוח חובות לפי חייב',
                        'category' => 'debts',
                    ],
                ],
            ],
            [
                'id' => 'members',
                'label' => 'דוחות מתפללים וניהול קהילה',
                'reports' => [
                    [
                        'id' => 'members_active',
                        'label' => 'דוח חברים פעילים',
                        'category' => 'members',
                    ],
                    [
                        'id' => 'members_recent',
                        'label' => 'דוח חברים שהצטרפו בשלושת החודשים האחרונים',
                        'category' => 'members',
                    ],
                    [
                        'id' => 'members_no_donation',
                        'label' => 'דוח מתפללים שלא תרמו בשלושת החודשים האחרונים',
                        'category' => 'members',
                    ],
                    [
                        'id' => 'members_no_auto_payment',
                        'label' => 'דוח חברי קהילה ללא תשלום אוטומטי',
                        'category' => 'members',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get configuration for a specific report type.
     */
    public function getReportConfig(string $reportTypeId): ?array
    {
        $configs = [
            'income_monthly' => $this->getIncomeMonthlyConfig(),
            'expenses_monthly' => $this->getExpensesMonthlyConfig(),
            'expenses_high' => $this->getExpensesHighConfig(),
            'donations_community' => $this->getDonationsCommunityConfig(),
            'donations_external' => $this->getDonationsExternalConfig(),
            'debts_open' => $this->getDebtsOpenConfig(),
            'debts_by_type' => $this->getDebtsByTypeConfig(),
            'debts_by_debtor' => $this->getDebtsByDebtorConfig(),
            'members_active' => $this->getMembersActiveConfig(),
            'members_recent' => $this->getMembersRecentConfig(),
            'members_no_donation' => $this->getMembersNoDonationConfig(),
            'members_no_auto_payment' => $this->getMembersNoAutoPaymentConfig(),
        ];

        return $configs[$reportTypeId] ?? null;
    }

    /**
     * Income Monthly Report Configuration
     */
    private function getIncomeMonthlyConfig(): array
    {
        return [
            'reportName' => 'דוח הכנסות חודשיות',
            'columns' => [
                ['id' => 'receipt_number', 'label' => 'מספר קבלה', 'required' => true],
                ['id' => 'receipt_date', 'label' => 'תאריך', 'required' => true],
                ['id' => 'hebrew_date', 'label' => 'תאריך עברי', 'required' => false],
                ['id' => 'payer_name', 'label' => 'שם משלם', 'required' => false],
                ['id' => 'amount', 'label' => 'סכום', 'required' => true],
                ['id' => 'type', 'label' => 'סוג הכנסה', 'required' => false],
                ['id' => 'payment_method', 'label' => 'אמצעי תשלום', 'required' => false],
                ['id' => 'status', 'label' => 'סטטוס', 'required' => false],
                ['id' => 'description', 'label' => 'תיאור', 'required' => false],
            ],
            'sortOptions' => [
                ['value' => 'receipt_date', 'label' => 'תאריך'],
                ['value' => 'amount', 'label' => 'סכום'],
                ['value' => 'payer_name', 'label' => 'שם משלם'],
                ['value' => 'type', 'label' => 'סוג הכנסה'],
            ],
            'filters' => [
                [
                    'key' => 'type',
                    'label' => 'סוג הכנסה',
                    'options' => [
                        ['value' => 'vows', 'label' => 'נדרים'],
                        ['value' => 'community_donations', 'label' => 'תרומות מהקהילה'],
                        ['value' => 'external_donations', 'label' => 'תרומות חיצוניות'],
                        ['value' => 'ascensions', 'label' => 'עליות'],
                        ['value' => 'online_donations', 'label' => 'תרומות אונליין'],
                        ['value' => 'membership_fees', 'label' => 'דמי חברים'],
                        ['value' => 'other', 'label' => 'אחר'],
                    ],
                ],
                [
                    'key' => 'status',
                    'label' => 'סטטוס',
                    'options' => [
                        ['value' => 'paid', 'label' => 'שולם'],
                        ['value' => 'pending', 'label' => 'ממתין'],
                    ],
                ],
                [
                    'key' => 'payment_method',
                    'label' => 'אמצעי תשלום',
                    'options' => [
                        ['value' => 'credit_card', 'label' => 'כרטיס אשראי'],
                        ['value' => 'cash', 'label' => 'מזומן'],
                        ['value' => 'bank_transfer', 'label' => 'העברה בנקאית'],
                        ['value' => 'check', 'label' => 'צ\'ק'],
                        ['value' => 'other', 'label' => 'אחר'],
                    ],
                ],
            ],
            'supportsDateRange' => true,
            'supportsResultLimit' => true,
        ];
    }

    /**
     * Expenses Monthly Report Configuration
     */
    private function getExpensesMonthlyConfig(): array
    {
        return [
            'reportName' => 'דוח הוצאות חודשיות',
            'columns' => [
                ['id' => 'date', 'label' => 'תאריך', 'required' => true],
                ['id' => 'hebrew_date', 'label' => 'תאריך עברי', 'required' => false],
                ['id' => 'description', 'label' => 'תיאור', 'required' => false],
                ['id' => 'amount', 'label' => 'סכום', 'required' => true],
                ['id' => 'type', 'label' => 'סוג הוצאה', 'required' => false],
                ['id' => 'supplier', 'label' => 'ספק', 'required' => false],
                ['id' => 'status', 'label' => 'סטטוס', 'required' => false],
                ['id' => 'frequency', 'label' => 'תדירות', 'required' => false],
            ],
            'sortOptions' => [
                ['value' => 'date', 'label' => 'תאריך'],
                ['value' => 'amount', 'label' => 'סכום'],
                ['value' => 'type', 'label' => 'סוג הוצאה'],
                ['value' => 'supplier', 'label' => 'ספק'],
            ],
            'filters' => [
                [
                    'key' => 'type',
                    'label' => 'סוג הוצאה',
                    'options' => [
                        ['value' => 'food', 'label' => 'מזון'],
                        ['value' => 'maintenance', 'label' => 'תחזוקת בית הכנסת'],
                        ['value' => 'equipment', 'label' => 'ציוד וריהוט'],
                        ['value' => 'insurance', 'label' => 'ביטוחים'],
                        ['value' => 'operations', 'label' => 'תפעול פעילויות'],
                        ['value' => 'suppliers', 'label' => 'ספקים ובעלי מקצוע'],
                        ['value' => 'management', 'label' => 'הנהלה ושכר'],
                    ],
                ],
                [
                    'key' => 'status',
                    'label' => 'סטטוס',
                    'options' => [
                        ['value' => 'paid', 'label' => 'שולם'],
                        ['value' => 'pending', 'label' => 'ממתין'],
                    ],
                ],
            ],
            'supportsDateRange' => true,
            'supportsResultLimit' => true,
        ];
    }

    /**
     * Expenses High Report Configuration (amount > 1000)
     */
    private function getExpensesHighConfig(): array
    {
        $config = $this->getExpensesMonthlyConfig();
        $config['reportName'] = 'דוח הוצאות גבוהות (מעל 1000₪)';
        return $config;
    }

    /**
     * Donations Community Report Configuration
     */
    private function getDonationsCommunityConfig(): array
    {
        $config = $this->getIncomeMonthlyConfig();
        $config['reportName'] = 'תרומות מהקהילה';
        // Filter is automatically applied to type = 'community_donations'
        return $config;
    }

    /**
     * Donations External Report Configuration
     */
    private function getDonationsExternalConfig(): array
    {
        $config = $this->getIncomeMonthlyConfig();
        $config['reportName'] = 'תרומות מחוץ לקהילה';
        // Filter is automatically applied to type = 'external_donations'
        return $config;
    }

    /**
     * Debts Open Report Configuration
     */
    private function getDebtsOpenConfig(): array
    {
        return [
            'reportName' => 'דוח חובות פתוחים',
            'columns' => [
                ['id' => 'debtor_name', 'label' => 'שם חייב', 'required' => true],
                ['id' => 'amount', 'label' => 'סכום', 'required' => true],
                ['id' => 'type', 'label' => 'סוג חוב', 'required' => false],
                ['id' => 'due_date', 'label' => 'תאריך יעד', 'required' => false],
                ['id' => 'hebrew_due_date', 'label' => 'תאריך יעד עברי', 'required' => false],
                ['id' => 'description', 'label' => 'תיאור', 'required' => false],
                ['id' => 'last_reminder', 'label' => 'תאריך תזכורת אחרונה', 'required' => false],
            ],
            'sortOptions' => [
                ['value' => 'due_date', 'label' => 'תאריך יעד'],
                ['value' => 'amount', 'label' => 'סכום'],
                ['value' => 'debtor_name', 'label' => 'שם חייב'],
                ['value' => 'type', 'label' => 'סוג חוב'],
            ],
            'filters' => [
                [
                    'key' => 'type',
                    'label' => 'סוג חוב',
                    'options' => [
                        ['value' => 'neder_shabbat', 'label' => 'נדר שבת'],
                        ['value' => 'tikun_nezek', 'label' => 'תיקון נזק'],
                        ['value' => 'dmei_chaver', 'label' => 'דמי חבר'],
                        ['value' => 'kiddush', 'label' => 'קידוש שבת'],
                        ['value' => 'neder_yom_shabbat', 'label' => 'נדר יום שבת'],
                        ['value' => 'other', 'label' => 'אחר'],
                    ],
                ],
            ],
            'supportsDateRange' => true,
            'supportsResultLimit' => true,
        ];
    }

    /**
     * Debts By Type Report Configuration
     */
    private function getDebtsByTypeConfig(): array
    {
        return $this->getDebtsOpenConfig();
    }

    /**
     * Debts By Debtor Report Configuration
     */
    private function getDebtsByDebtorConfig(): array
    {
        return $this->getDebtsOpenConfig();
    }

    /**
     * Members Active Report Configuration
     */
    private function getMembersActiveConfig(): array
    {
        return [
            'reportName' => 'דוח חברים פעילים',
            'columns' => [
                ['id' => 'member_number', 'label' => 'מספר חבר', 'required' => false],
                ['id' => 'full_name', 'label' => 'שם מלא', 'required' => true],
                ['id' => 'type', 'label' => 'סוג חבר', 'required' => false],
                ['id' => 'email', 'label' => 'אימייל', 'required' => false],
                ['id' => 'mobile', 'label' => 'נייד', 'required' => false],
                ['id' => 'phone', 'label' => 'טלפון', 'required' => false],
                ['id' => 'address', 'label' => 'כתובת', 'required' => false],
                ['id' => 'city', 'label' => 'עיר', 'required' => false],
            ],
            'sortOptions' => [
                ['value' => 'full_name', 'label' => 'שם מלא'],
                ['value' => 'type', 'label' => 'סוג חבר'],
                ['value' => 'member_number', 'label' => 'מספר חבר'],
            ],
            'filters' => [
                [
                    'key' => 'type',
                    'label' => 'סוג חבר',
                    'options' => [
                        ['value' => 'permanent', 'label' => 'קבוע'],
                        ['value' => 'family_member', 'label' => 'בן משפחה'],
                        ['value' => 'guest', 'label' => 'אורח'],
                        ['value' => 'supplier', 'label' => 'ספק'],
                        ['value' => 'other', 'label' => 'אחר'],
                    ],
                ],
            ],
            'supportsDateRange' => false,
            'supportsResultLimit' => true,
        ];
    }

    /**
     * Members Recent Report Configuration
     */
    private function getMembersRecentConfig(): array
    {
        $config = $this->getMembersActiveConfig();
        $config['reportName'] = 'דוח חברים שהצטרפו בשלושת החודשים האחרונים';
        $config['supportsDateRange'] = false; // Date range is fixed to last 3 months
        return $config;
    }

    /**
     * Members No Donation Report Configuration
     */
    private function getMembersNoDonationConfig(): array
    {
        $config = $this->getMembersActiveConfig();
        $config['reportName'] = 'דוח מתפללים שלא תרמו בשלושת החודשים האחרונים';
        $config['supportsDateRange'] = false; // Date range is fixed to last 3 months
        return $config;
    }

    /**
     * Members No Auto Payment Report Configuration
     */
    private function getMembersNoAutoPaymentConfig(): array
    {
        $config = $this->getMembersActiveConfig();
        $config['reportName'] = 'דוח חברי קהילה ללא תשלום אוטומטי';
        $config['supportsDateRange'] = false;
        return $config;
    }
}

