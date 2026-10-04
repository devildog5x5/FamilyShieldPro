<?php
declare(strict_types=1);

final class Guides
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return [
            self::olderParents(),
            self::beforeYouPay(),
            self::giftCards(),
            self::phoneList(),
            self::emergencyTexts(),
        ];
    }

    /** @return list<string> */
    public static function paths(): array
    {
        $paths = [];
        foreach (self::all() as $page) {
            $paths[] = (string) $page['path'];
        }
        return $paths;
    }

    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $page) {
            if ($page['slug'] === $slug) {
                return $page;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public static function byPath(string $path): ?array
    {
        foreach (self::all() as $page) {
            if ($page['path'] === $path) {
                return $page;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    private static function olderParents(): array
    {
        return [
            'slug' => 'older-parents',
            'path' => '/guides/older-parents',
            'nav' => 'Older parents',
            'title' => 'Protect older parents from payment scams · OurCircle',
            'description' => 'How a household uses OurCircle so an older parent can pause before gift cards, wires, or a fake emergency. Not a guarantee.',
            'keywords' => 'Family Shield Pro, OurCircle, protect older parents from scams, elder payment fraud, gift card scam, family pause before paying',
            'h1' => 'Protect older parents from payment scams',
            'lede' => 'You cannot sit beside a parent for every text, call, or “urgent” payment. You can agree on one pause, keep the real phone numbers in one place, and look at the same screenshot together before anyone pays.',
            'sections' => [
                [
                    'h2' => 'Agree on the pause before a scare arrives',
                    'paragraphs' => [
                        'Say this out loud, the way you would say it at the kitchen table: never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.',
                        'Independently verified means a number or website you already had — the back of the card, a statement, or a contact saved on a calm day. It does not mean the phone number or the link inside the message that just arrived.',
                    ],
                ],
                [
                    'h2' => 'Write down the real numbers this week',
                    'paragraphs' => [
                        'Do this while nothing is urgent. Copy the bank number from the back of the card or a statement. Add the doctor’s office, the insurer, the utility, and one relative who will pick up.',
                        'OurCircle keeps that list for the household. A later check compares a message to those saved numbers and sites, not to a stranger who wrote a number in the text. A match is not a stamp that the message is safe. A missing number is not proof that it is a scam. Either way, you still call using a number you already had.',
                    ],
                ],
                [
                    'h2' => 'When a message shows up',
                    'paragraphs' => [
                        'Bring it into the circle: paste the text or email, upload a screenshot, or add the phone number or website. OurCircle reads the paste for warning signs. It does not decide the request is safe, real, or a scam.',
                        'The check slows down for a few patterns it is built to notice: a gift card, crypto, or wire; a push to keep it secret; a relative “in trouble”; a rush to act right now; or a request for a password, PIN, or account number. No classic phrase is not a clean bill of health. The next step is still to pause and call.',
                    ],
                    'list' => [
                        'Do not tap links in the message, and do not call the number it gives you.',
                        'Ask someone in the circle to look at the same check.',
                        'If money is about to move, use “Please call me before I pay.” The alert names the person who pasted the check, so the family knows who to call.',
                    ],
                ],
                [
                    'h2' => 'What this does not do',
                    'paragraphs' => [
                        'OurCircle does not read a parent’s phone by itself. Someone has to bring the message in. It does not freeze a card, reverse a payment, call the bank, or file a fraud report for you. A 14-day trial, then Family monthly at $14.99 or Family yearly at $119.99, pays for the family tool. Paying does not make a request safe.',
                        'A circle holds up to five people. After a trial ends, the trusted list and past checks stay readable. New checks, invites, and call-me wait until the owner pays. We do not sell people’s information.',
                    ],
                ],
                [
                    'h2' => 'If money already went out',
                    'paragraphs' => [
                        'Speed matters more than shame. Call the bank or card company on the number printed on the card or a statement — not a number from the text — and ask to freeze the card and dispute the charge. If a gift card, crypto, or wire already left, tell that issuer the same day. Those payments are hard to undo. Still report them.',
                    ],
                    'links' => [
                        ['href' => 'https://reportfraud.ftc.gov', 'label' => 'ReportFraud.ftc.gov', 'note' => 'Official U.S. fraud report.'],
                        ['href' => 'https://www.ic3.gov', 'label' => 'IC3.gov', 'note' => 'FBI internet-crime report.'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function beforeYouPay(): array
    {
        return [
            'slug' => 'ask-family-before-you-pay',
            'path' => '/guides/ask-family-before-you-pay',
            'nav' => 'Before you pay',
            'title' => 'Ask your family before you send money · OurCircle',
            'description' => 'Ask your family before you send money. OurCircle shows warning signs and a call-me button. It never stamps a request as safe.',
            'keywords' => 'Family Shield Pro, OurCircle, family approval before sending money, pause before you pay, call me before I pay, scam text',
            'h1' => 'Ask your family before you send money',
            'lede' => 'A payment ask gets easier to refuse when another person can see it. OurCircle is a shared pause for a household: the text, the call, the prize, or the “urgent” bill sits in one place, and someone you already trust can look before money moves.',
            'sections' => [
                [
                    'h2' => 'Three steps the product actually walks',
                    'list' => [
                        'Bring the request in. Paste the email or text, upload a screenshot, or enter a phone number, website, or payment ask. You do not have to retype numbers that are already in the paste.',
                        'Read the warning signs. OurCircle points at phrases and lookalike sites it knows how to notice, and at whether a number is on your trusted list. It will not say the request is safe.',
                        'Involve the circle. Up to five people share one household. Ask one of them to look. If it is urgent, tap “Please call me before I pay.” That alert names the person who pasted the check, so the person about to pay is not left alone with the message.',
                    ],
                ],
                [
                    'h2' => 'What “verified” means here',
                    'paragraphs' => [
                        'Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified. That means you confirmed it through a number or site you already trust. A link in the suspicious message is not that confirmation. A paid OurCircle plan is not that confirmation either.',
                        'Hang up and call back on a number from the back of the card, a statement, or your trusted list. If they insist you stay on the line, that is a reason to pause, not a reason to pay.',
                    ],
                ],
                [
                    'h2' => 'What you are paying for',
                    'paragraphs' => [
                        'A new circle includes a 14-day trial. Then Family monthly is $14.99 or Family yearly is $119.99. The circle owner is the one who pays. You keep what you entered, including the trusted list and past checks, and we do not sell people’s information. If the trial ends, new checks, invites, and call-me wait until the owner pays. Old checks stay readable.',
                        'This application offers guidance, not a guarantee. Use it to slow down with your family. Do not use it as a stamp.',
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function giftCards(): array
    {
        return [
            'slug' => 'gift-cards-crypto-wires',
            'path' => '/guides/gift-cards-crypto-wires',
            'nav' => 'Gift cards and wires',
            'title' => 'Gift card, crypto, and wire payment asks · OurCircle',
            'description' => 'Gift cards, crypto, and wires are hard to undo. How OurCircle flags those payment asks, and what to do before anyone pays.',
            'keywords' => 'Family Shield Pro, OurCircle, gift card scam, crypto payment scam, wire transfer scam, Zelle scam, pause before you pay',
            'h1' => 'Gift cards, crypto, and wires are hard to undo',
            'lede' => 'A real bank, a tax office, a utility, or a relative in trouble almost never needs you to buy gift cards, send cryptocurrency, or wire money because of a text. When a message asks for that, stop before anyone drives to the store.',
            'sections' => [
                [
                    'h2' => 'What OurCircle treats as a reason to pause',
                    'paragraphs' => [
                        'Paste the message into a check. If it asks for a gift card, an Apple, Google Play, or Steam card, bitcoin or other crypto, a wire, Western Union, MoneyGram, or Zelle, the check says to pause and not to pay or share anything yet.',
                        'That headline is a warning, not a verdict. OurCircle does not confirm the sender, and it does not declare the message genuine when those words are missing. Other rushes — a password, a one-time code, “keep this secret,” or a relative in trouble — are also reasons it tells you to stop.',
                    ],
                ],
                [
                    'h2' => 'Before anyone buys the card or sends the code',
                    'list' => [
                        'Do not read card numbers or photos of the back of a gift card to anyone who contacted you.',
                        'Do not stay on the phone while you pay. Hang up.',
                        'Call the company on a number you already had, or call a person in your circle.',
                        'Put the message in OurCircle and tap “Please call me before I pay” if money is close to moving. The alert names the person who pasted it.',
                    ],
                ],
                [
                    'h2' => 'If the payment already left',
                    'paragraphs' => [
                        'Tell the gift-card seller, the crypto exchange, or the wire service immediately, and call your bank on the number from the card. Say what you sent. These payments are hard to reverse. Reporting still matters, and OurCircle cannot get the money back for you.',
                    ],
                    'links' => [
                        ['href' => 'https://reportfraud.ftc.gov', 'label' => 'ReportFraud.ftc.gov', 'note' => 'Official U.S. fraud report.'],
                        ['href' => 'https://www.ic3.gov', 'label' => 'IC3.gov', 'note' => 'FBI internet-crime report.'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function phoneList(): array
    {
        return [
            'slug' => 'trusted-phone-list',
            'path' => '/guides/trusted-phone-list',
            'nav' => 'Phone numbers',
            'title' => 'Save real bank and family phone numbers · OurCircle',
            'description' => 'Save real bank, doctor, and family numbers in OurCircle. Checks compare a message to your list, not to a link in the text.',
            'keywords' => 'Family Shield Pro, OurCircle, trusted phone list, bank phone number, verify a caller, lookalike website, family contacts',
            'h1' => 'Save the real phone numbers before you need them',
            'lede' => 'The useful number is the one you wrote down on a calm day. A check can compare a new message to that list. It should not send you back to the phone number printed inside the message.',
            'sections' => [
                [
                    'h2' => 'What to save, and where to copy it from',
                    'paragraphs' => [
                        'Save banks, doctors and clinics, insurers, utilities, and family. In OurCircle each saved row can hold a name, a phone number, a website, and a short note.',
                        'Copy the phone number from the back of the card, a paper statement, or the organization’s own site that you typed yourself. Do not copy it from a text, an email link, or a search ad you have not used before.',
                    ],
                ],
                [
                    'h2' => 'How a check uses the list',
                    'paragraphs' => [
                        'OurCircle pulls phone numbers and links out of what you paste. A number is treated as “on the list” when it matches a saved number. If it does not match, the check says to call using a number from a statement, the card, or a contact you already saved — not the one in the message.',
                        'A website is compared with sites you saved. If the address resembles a well-known company but is not an exact match, the check calls that out as a lookalike. If it is an exact well-known address, the check still tells you to call a number you already have, not a number in the message. A match to your list is a clue. It is not a stamp that the request is safe.',
                    ],
                ],
                [
                    'h2' => 'Keep the list inside the circle',
                    'paragraphs' => [
                        'People you invite can see the trusted list and the checks for that household, up to five people. Do not put full account numbers or passwords in the notes. The list is for “which phone do we already trust,” not for storing secrets a message is trying to collect.',
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function emergencyTexts(): array
    {
        return [
            'slug' => 'family-emergency-texts',
            'path' => '/guides/family-emergency-texts',
            'nav' => 'Emergency texts',
            'title' => 'Family emergency texts and the grandparent scam · OurCircle',
            'description' => 'A text says a relative is in trouble. Pause in OurCircle, keep the screenshot, and call a number you already saved. Not a safe stamp.',
            'keywords' => 'Family Shield Pro, OurCircle, grandparent scam, grandkid in trouble text, family emergency scam, bail money text, pause before you pay',
            'h1' => 'When a text says a relative is in trouble',
            'lede' => 'A message says a grandchild is in jail, someone crashed a car, or a parent needs bail money and you must not tell the rest of the family. Treat that as a reason to pause and call a number you already have.',
            'sections' => [
                [
                    'h2' => 'What to do in the first few minutes',
                    'list' => [
                        'Do not send money, gift cards, or crypto while you are still in that conversation.',
                        'Do not keep it secret. The request for secrecy is part of the pressure.',
                        'Hang up or stop replying. Call the relative on a number you saved before today — their real phone, or another family member who would know where they are.',
                        'Do not call the number that arrived inside the text.',
                    ],
                    'paragraphs' => [
                        'If you cannot reach them, call someone else who would know, still using a number you already had. A voice on the phone can be faked. A story that forbids you to check with the family is the part to refuse.',
                    ],
                ],
                [
                    'h2' => 'Put the screenshot where the family can see it',
                    'paragraphs' => [
                        'Paste the text or upload the screenshot into OurCircle so the household is looking at one copy. The check is written to pause on stories about jail, arrest, bail, a grandkid, a crash, or “in trouble,” and on lines that say not to tell mom or dad or to keep it between you.',
                        'That is a warning sign, not a ruling. If the words are different, you still pause. Tap “Please call me before I pay” when you are close to sending something. The alert names the person who brought the message in.',
                    ],
                ],
                [
                    'h2' => 'If you already paid',
                    'paragraphs' => [
                        'Call your bank on the number from the card and tell the gift-card or wire company what left. OurCircle cannot reverse it. Report what happened so there is an official record.',
                    ],
                    'links' => [
                        ['href' => 'https://reportfraud.ftc.gov', 'label' => 'ReportFraud.ftc.gov', 'note' => 'Official U.S. fraud report.'],
                        ['href' => 'https://www.ic3.gov', 'label' => 'IC3.gov', 'note' => 'FBI internet-crime report.'],
                    ],
                ],
            ],
        ];
    }
}
