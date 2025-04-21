<?php

namespace Api\Controllers;

use Infrastructure\Database\IDbContext;
use Infrastructure\Kiwi\core\http\HttpMethod;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use Infrastructure\Kiwi\core\http\RouterController;

require_once __DIR__ . '/../../Infrastructure/Kiwi/core/http/RouterController.php';
require_once __DIR__ . '/../../Infrastructure/Kiwi/core/http/HttpMethod.php';

class OrderItemsController extends RouterController
{
    private IDbContext $dbContext;

    public function __construct(
        IDbContext $dbContext,
        string     $prefix = '',
        array      $middleware = []
    ) {
        $this->dbContext = $dbContext;
        parent::__construct($prefix, $middleware);
    }

    public function registerController(): void
    {
        $this->route('', 'GetAllOrderItems', HttpMethod::GET);
        $this->route('/:id', 'GetOrderItem', HttpMethod::GET);
        $this->route('/', 'CreateOrderItem', HttpMethod::POST);
        $this->route('/:id', 'UpdateOrderItem', HttpMethod::PUT);
        $this->route('/:id', 'DeleteOrderItem', HttpMethod::DELETE);
        $this->route('/assign', 'AssignOrderItemToOrder', HttpMethod::POST);
    }

    public function GetAllOrderItems(Request $req, Response $res)
    {
        $items = $this->dbContext->table('order_items')->select('*')->get();
        Response::json($items, Response::HTTP_OK);
    }

    public function GetOrderItem(Request $req, Response $res)
    {
        $id = $req->getParameter('id');
        $item = $this->dbContext->table('order_items')->select('*')->where('id', $id)->get();

        if (!$item) {
            Response::json(["error" => "Order Item #$id not found"], Response::HTTP_NOT_FOUND);
            return;
        }

        Response::json($item[0], Response::HTTP_OK);
    }

    public function CreateOrderItem(Request $req, Response $res)
    {
        $data = $req->getJsonBody();

        $required = ['value', 'order_id', 'name'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::json(["error" => "Missing field: $field"], Response::HTTP_BAD_REQUEST);
                return;
            }
        }

        $id = $this->dbContext->table('order_items')->insert([
            'name' => $data['name'],
            'value' => $data['value'],
            'order_id' => $data['order_id'],
            'creation_date' => date('Y-m-d H:i:s')
        ]);

        Response::json(['id' => $id], Response::HTTP_CREATED);
    }

    public function UpdateOrderItem(Request $req, Response $res)
    {
        $id = $req->getParameter('id');
        $data = $req->getJsonBody();

        $existing = $this->dbContext->table('order_items')->select('*')->where('id', $id)->get();
        if (!$existing) {
            Response::json(["error" => "Order Item #$id not found"], Response::HTTP_NOT_FOUND);
            return;
        }

        $allowedFields = ['value', 'order_id', 'name'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->dbContext->table('order_items')->where('id', $id)->update($updateData);
        }

        Response::json(['message' => 'Order item updated'], Response::HTTP_OK);
    }

    public function DeleteOrderItem(Request $req, Response $res)
    {
        $id = $req->getParameter('id');
        $existing = $this->dbContext->table('order_items')->select('*')->where('id', $id)->get();

        if (!$existing) {
            Response::json(["error" => "Order Item #$id not found"], Response::HTTP_NOT_FOUND);
            return;
        }

        $this->dbContext->table('order_items')->where('id', $id)->delete();
        Response::json(['message' => 'Order item deleted'], Response::HTTP_OK);
    }

    public function AssignOrderItemToOrder(Request $req, Response $res)
    {
        $data = $req->getJsonBody();

        if (!isset($data['item_id'], $data['order_id'])) {
            Response::json(["error" => "Missing 'item_id' or 'order_id'"], Response::HTTP_BAD_REQUEST);
            return;
        }

        $this->dbContext
            ->table('order_items')
            ->where('id', $data['item_id'])
            ->update(['order_id' => $data['order_id']]);

        Response::json(['message' => "Item #{$data['item_id']} assigned to order #{$data['order_id']}"]);
    }
}
