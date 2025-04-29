<?php
namespace App\Service;

class MeetLinkGenerator
{
    private array $meetLinks = [
        'https://meet.google.com/jdu-cqxr-bxw',
        'https://meet.google.com/brd-dpym-pbm',
        'https://meet.google.com/vqp-yiib-uqo',
        'https://meet.google.com/erh-jdsv-ukw',
        'https://meet.google.com/jby-qxqa-xqu',
        'https://meet.google.com/ffa-dtzh-iaf',
        'https://meet.google.com/gnt-zajy-iop',
        'https://meet.google.com/xgy-tsur-udq',
        'https://meet.google.com/qnz-svvn-dbo'
    ];

    public function CreateMeetLink(): string
    {
        return $this->meetLinks[array_rand($this->meetLinks)];
    }
}