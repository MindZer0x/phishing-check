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

    // Urgency and pressure phrases (German and English). Like all phrase
    // lists below, they match as whole words, ignoring case; spaces and
    // hyphens inside a phrase may vary or be left out.
    'pressure_phrases' => [
        'sofort', 'umgehend', 'unverzüglich', 'dringend', 'eilt',
        'gesperrt', 'sperrung', 'eingeschränkt', 'deaktiviert',
        'innerhalb von 24 stunden', 'innerhalb von 48 stunden',
        'innerhalb von 72 stunden', 'innerhalb 24 stunden',
        'innerhalb 48 stunden', 'letzte mahnung', 'letzte warnung',
        'letzte erinnerung', 'rechtliche schritte', 'inkasso',
        'immediately', 'urgent', 'urgently', 'suspended', 'locked',
        'deactivated', 'within 24 hours', 'within 48 hours',
        'within 72 hours', 'final notice', 'final warning',
        'action required', 'legal action',
    ],

    // Credentials, payment details and means of payment. A request for them
    // is found when one of these and one of the request words below appear
    // in the same sentence.
    'sensitive_data' => [
        'passwort', 'passworts', 'kennwort', 'kennworts', 'pin', 'tan',
        'zugangsdaten', 'anmeldedaten', 'logindaten', 'login daten',
        'kreditkarte', 'kreditkartendaten', 'kreditkartennummer',
        'kartennummer', 'prüfnummer', 'iban', 'bankverbindung', 'bankdaten',
        'kontodaten', 'überweisung',
        'zahlungsdaten', 'zahlungsinformationen', 'betrag', 'rechnungsbetrag',
        'gutschein', 'gutscheine', 'gutscheincode', 'gutscheincodes',
        'geschenkkarte', 'geschenkkarten', 'guthabenkarte', 'guthabenkarten',
        'google play karte', 'google play karten', 'itunes karte',
        'itunes karten', 'amazon gutschein', 'amazon gutscheine',
        'password', 'passcode', 'credentials', 'login details',
        'credit card', 'card number', 'cvv', 'bank details',
        'payment details', 'payment', 'gift card', 'gift cards',
        'voucher', 'vouchers', 'wire transfer',
    ],

    // Words that ask the reader to hand over or pay something, including
    // the first part of German separable verbs ("geben Sie ... ein").
    'request_words' => [
        'geben', 'eingeben', 'angeben', 'bestätigen', 'bestätigung',
        'aktualisieren', 'verifizieren', 'überprüfen', 'hinterlegen',
        'mitteilen', 'senden', 'schicken', 'übermitteln', 'kaufen',
        'besorgen', 'überweisen', 'bezahlen', 'begleichen', 'veranlassen',
        'melden', 'anmelden', 'einloggen',
        'enter', 'confirm', 'verify', 'update', 'provide', 'send', 'share',
        'buy', 'purchase', 'pay', 'log in', 'sign in',
    ],

    // File extensions (without the dot) of attachments that can run code:
    // programs, scripts, macro-enabled Office documents, shortcuts, disk
    // images and archives that often hide them. Not .com: it would match
    // every .com domain.
    'dangerous_attachment_extensions' => [
        'exe', 'scr', 'pif', 'bat', 'cmd', 'msi', 'cpl', 'jar', 'js',
        'jse', 'vbs', 'vbe', 'wsf', 'hta', 'ps1', 'lnk', 'iso', 'img', 'vhd',
        'docm', 'xlsm', 'pptm', 'dotm', 'xlam', 'zip', 'rar', '7z', 'gz',
        'tar', 'ace', 'cab',
    ],

    // Phrases that address an AI assessing the email (prompt injection,
    // ADR-0001). Only instructions, not mere mentions of AI such as "KI-
    // Assistent", which product newsletters use. Write "e-mail" so that
    // "E Mail" and "Email" match as well.
    'ai_instruction_phrases' => [
        'vorherige anweisungen', 'vorherigen anweisungen',
        'vorige anweisungen', 'vorigen anweisungen',
        'bisherige anweisungen', 'bisherigen anweisungen',
        'obige anweisungen', 'obigen anweisungen',
        'bewerte diese e-mail', 'bewerten sie diese e-mail',
        'stufe diese e-mail', 'stufen sie diese e-mail',
        'diese e-mail ist sicher', 'diese e-mail ist harmlos',
        'diese e-mail ist unbedenklich', 'diese mail ist sicher',
        'previous instructions', 'prior instructions', 'above instructions',
        'rate this e-mail', 'classify this e-mail', 'mark this e-mail',
        'this e-mail is safe', 'this e-mail is harmless', 'system prompt',
    ],

];
