<?php

namespace app\domain\Enums;

enum OrderStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case ON_ROUTE = 'on_route';
    case RETURNING = 'returning';
    case RECEIVING = 'receiving';
}