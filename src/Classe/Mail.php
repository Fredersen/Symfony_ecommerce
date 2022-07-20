<?php

namespace App\Classe;

use Mailjet\Resources;

class Mail
{

    private $api_key = 'c0ee146507702e23f8e0a355960d38c7';
    private $secret_key = "";
    public function send($to_email, $to_name, $subject, $content)
    {
        $mj = new \Mailjet\Client($this->api_key, $this->secret_key,true,['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "naim.jhuboo@gmail.com",
                        'Name' => "Symfony Ecommerce"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateId' => 4080079,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'variables' => [
                        'content' => $content
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
    }
}
