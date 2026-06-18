<?php

namespace DirectoryTree\PrivacyFilter;

/**
 * Known privacy-filter entity types.
 */
enum EntityType: string
{
    case Person = 'person';
    case Email = 'email';
    case Phone = 'phone';
    case Address = 'address';
    case CreditCard = 'credit_card';
    case Date = 'date';
    case IpAddress = 'ip_address';
    case Url = 'url';

    case PrivatePerson = 'private_person';
    case PrivateEmail = 'private_email';
    case PrivatePhone = 'private_phone';
    case PrivateAddress = 'private_address';
    case PrivateCreditCard = 'private_credit_card';
    case PrivateDate = 'private_date';
    case PrivateIpAddress = 'private_ip_address';
    case PrivateUrl = 'private_url';
}
