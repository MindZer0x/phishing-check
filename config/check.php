<?php

/*
|--------------------------------------------------------------------------
| Heuristic analysis
|--------------------------------------------------------------------------
|
| Lists the rule-based signals work with. Extend them here; no code change
| is needed. Entries are lower case.
|
*/

return [

    // Brands that phishing emails commonly impersonate, as written in names.
    'brands' => [
        'adobe', 'amazon', 'apple', 'commerzbank', 'comdirect', 'deutsche bank',
        'dhl', 'dkb', 'docusign', 'dpd', 'dropbox', 'ebay', 'facebook', 'google',
        'ing', 'instagram', 'linkedin', 'microsoft', 'netflix',
        'paypal', 'postbank', 'sparkasse', 'telekom', 'ups', 'vodafone',
        'whatsapp',
    ],

    // Registrable domains that brands use themselves although the brand name
    // is written together with other words, e.g. for images in their emails.
    'brand_owned_domains' => [
        'amazonaws.com', 'googleapis.com', 'googleusercontent.com',
        'microsoftonline.com', 'paypalobjects.com',
    ],

    // Registrable domains where anyone can get a free email address.
    'free_mail_domains' => [
        'aol.com', 'aol.de', 'freenet.de', 'gmail.com', 'gmx.at', 'gmx.ch',
        'gmx.com', 'gmx.de', 'gmx.net', 'googlemail.com', 'hotmail.com',
        'hotmail.de', 'icloud.com', 'live.com', 'live.de', 'mail.com',
        'mail.de', 'mail.ru', 'mailbox.org', 'me.com', 'msn.com',
        'outlook.com', 'outlook.de', 'posteo.de', 'proton.me',
        'protonmail.com', 't-online.de', 'web.de', 'yahoo.com', 'yahoo.de',
        'yandex.com', 'yandex.ru',
    ],

    // Words (German and English) that make a sender display name look like
    // an organisation rather than a person. They also match as the end of a
    // compound, e.g. "service" in "Kundenservice".
    'organisation_words' => [
        'abteilung', 'account', 'admin', 'administrator', 'bank', 'billing',
        'buchhaltung', 'dienst', 'helpdesk', 'kasse', 'konto', 'postfach',
        'security', 'service', 'sicherheit', 'support', 'team', 'verwaltung',
    ],

    // Registrable domains of URL shortening services.
    'url_shorteners' => [
        'bit.ly', 'buff.ly', 'cutt.ly', 'goo.gl', 'is.gd', 'ow.ly', 'rb.gy',
        'rebrand.ly', 's.id', 'shorturl.at', 't.co', 't.ly', 'tiny.cc',
        'tinyurl.com', 'v.gd',
    ],

];
