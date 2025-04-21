<?php

namespace Api\Controllers;

use Infrastructure\Database\IDbContext;
use Infrastructure\Kiwi\core\http\HttpMethod;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use Infrastructure\Kiwi\core\http\RouterController;

require_once __DIR__ . '/../../Infrastructure/Kiwi/core/http/RouterController.php';
require_once __DIR__ . '/../../Infrastructure/Kiwi/core/http/HttpMethod.php';

class OrdersController extends RouterController
{
    private IDbContext $dbContext;

    public function __construct(IDbContext $dbContext, string $prefix = '', array $middleware = [])
    {
        $this->dbContext = $dbContext;
        parent::__construct($prefix, $middleware);
    }

    public function registerController(): void
    {
        $this->route('', 'GetAllOrders', HttpMethod::GET);
        $this->route('/:id', 'GetOrder', HttpMethod::GET);
        $this->route('/', 'CreateOrder', HttpMethod::POST);
        $this->route('/:id', 'UpdateOrder', HttpMethod::PUT);
        $this->route('/:id', 'DeleteOrder', HttpMethod::DELETE);
    }

    public function GetAllOrders(Request $req, Response $res)
    {
        $page = max((int)$req->getQueryParam('page', 1), 1);
        $limit = min((int)$req->getQueryParam('limit', 20), 100);
        $offset = ($page - 1) * $limit;

        $query = $this->dbContext->table('orders')->select('*');
        $result = $query->limit($limit)->offset($offset)->paginate();

        Response::json([
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int)ceil($result['total'] / $limit),
            'data' => $result['data'],
        ], Response::HTTP_OK);
    }

    public function GetOrder(Request $req, Response $res)
    {
        $orderId = $req->getParameter('id');

        $orders = $this->dbContext
            ->table('orders')
            ->select('*')
            ->where('id', $orderId)
            ->include('order_items', 'order_id', 'id')
            ->get();

        if (empty($orders)) {
            Response::notFound("Order #$orderId not found");
            return;
        }

        Response::json($orders[0], Response::HTTP_OK);

    }

    public function CreateOrder(Request $req, Response $res)
    {
        $data = $req->getJsonBody();

        $required = ['name', 'amount_in_stock', 'price', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::json(["error" => "Missing field: $field"], Response::HTTP_BAD_REQUEST);
                return;
            }
        }
        $orderId = $this->dbContext->table('orders')->insert([
            'name' => $data['name'],
            'amount_in_stock' => $data['amount_in_stock'],
            'price' => $data['price'],
            'status' => $data['status'],
            'date_of_creation' => date('Y-m-d H:i:s')
        ]);

        Response::json(['id' => $orderId], Response::HTTP_CREATED);
    }

    public function UpdateOrder(Request $req, Response $res)
    {
        $orderId = $req->getParameter('id');
        $data = $req->getJsonBody();

        $existing = $this->dbContext->table('orders')->select('*')->where('id', $orderId)->get();
        if (empty($existing)) {
            Response::notFound("Order #$orderId not found");
            return;
        }

        $allowedFields = ['name', 'amount_in_stock', 'price', 'status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->dbContext->table('orders')->where('id', $orderId)->update($updateData);
        }

        Response::json(['message' => 'Order updated'], Response::HTTP_OK);
    }


    public function DeleteOrder(Request $req, Response $res)
    {
        $orderId = $req->getParameter('id');
        $existing = $this->dbContext->table('orders')->select('*')->where('id', $orderId)->get();

        if (empty($existing)) {
            Response::notFound("Order #$orderId not found");
            return;
        }

        $this->dbContext->table('orders')->where('id', $orderId)->delete();
        Response::json(['message' => 'Order deleted'], Response::HTTP_OK);
    }

}
