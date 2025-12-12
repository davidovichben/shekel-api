<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'ברוכים הבאים למערכת',
            'חוב חדש נוסף',
            'קבלה הופקה בהצלחה',
            'תזכורת: חובות פתוחים',
            'עדכון מערכת',
            'חבר חדש הצטרף',
            'תשלום התקבל',
            'הודעה נשלחה בהצלחה',
            'דוח חודשי מוכן',
            'תזכורת תשלום',
        ];

        $contents = [
            'שמחים שהצטרפתם אלינו! כאן תוכלו לנהל את כל פעילות בית הכנסת במקום אחד.',
            'חוב חדש נוסף עבור חבר הקהילה בסך 500 ש"ח.',
            'קבלה הופקה בהצלחה ונשלחה לחבר הקהילה.',
            'ישנם חובות פתוחים הממתינים לתשלום. לחץ כאן לצפייה ברשימה המלאה.',
            'המערכת עודכנה בהצלחה לגרסה החדשה.',
            'חבר קהילה חדש נרשם למערכת.',
            'תשלום התקבל בהצלחה וקבלה נשלחה.',
            'ההודעה נשלחה בהצלחה לכל הנמענים.',
            'הדוח החודשי מוכן לצפייה.',
            'תזכורת: יש לשלם את החוב עד סוף החודש.',
        ];

        $types = ['member', 'income'];
        $memberIds = \App\Models\Member::pluck('id')->toArray();

        for ($i = 0; $i < 100; $i++) {
            $type = $types[array_rand($types)];
            Notification::create([
                'business_id' => 1,
                'title' => $titles[array_rand($titles)],
                'content' => $contents[array_rand($contents)],
                'is_read' => rand(0, 1) === 1,
                'type' => $type,
                'type_id' => $type === 'member' && !empty($memberIds) ? $memberIds[array_rand($memberIds)] : null,
            ]);
        }
    }
}
