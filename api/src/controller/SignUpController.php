<?php
require_once __DIR__ . '/../model/SignUp.php';
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use api\model\SignUp;

class SignUpController{

    private $key = "CI6IkpXVCJ9";
    private $extraArgument;
    private $SignUp;

    public function __construct(private $db ,private $requestMethod, ...$extraArgument)
    {
        $this->extraArgument = $extraArgument;
        $this->SignUp = new SignUp($db);
    }

    public function test(){
        // $test = $this->auth->AuthTest();
        if ($this->requestMethod) {
            // echo json_encode($this->queryArray["id"]);
            echo $this->requestMethod;
            echo json_encode([$this->extraArgument, $this->SignUp->testFunction()]);
            exit;
        }else{
            echo json_encode("No ID");
            exit;
        }
    }

    public function ProcessRequest(){
        switch ($this->requestMethod) {
            case 'POST':
                $response = $this->signUp();
                break;
            default:
                $response = $this->notFoundResponse();
                break;
        }
        http_response_code($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
        exit;
    }

    private function signUp(): array
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);

        if(!$this->validateInput($data)){

            $this->unprocessableEntityResponse();
            
        }

        $this->SignUp->username = $data['username'];

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $this->SignUp->password = $hashed;

        $rows = count($this->SignUp->read());

        if($rows >= 1){

            return $this->usernameExist();

        }

        $signup = $this->SignUp->create();

        if (!$signup){

            return $this->createErrorResponse();

        }

        $JWTToken = $this->generateJWTToken($signup);

        return $this->createdResponse($JWTToken);
    }

    private function generateJWTToken($id): string
    {
        $payload = [
            'iss' => $_SERVER["SERVER_NAME"], //issuer(who created and signed this token)
            'iat' => time(),//issued at
            'exp' => strtotime("+1 hour"),//expiration time
            'id' => $id
        ];

        $encode = JWT::encode($payload, $this->key, 'HS256');

        return $encode;
    }

    private function validateInput($input): bool 
    {
        return isset($input['username']) && isset($input['password']);
    }

    private function createdResponse($data = ""): array
    {
        $response['status_code_header'] = 201;
        $response['body'] = json_encode($data);
        return $response;
    }

    private function createErrorResponse(): array
    {
        $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
        $response['body'] = json_encode([
            'error' => 'Something went wrong while inserting to database'
        ]);
        return $response;
    }

    private function unprocessableEntityResponse(): array
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => 'Invalid input'
        ]);
        return $response;
    }

    private function notFoundResponse(): array
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode([
            'error' => 'Not found'
        ]);
        return $response;
    }
    private function usernameExist(): array
    {
        $response['status_code_header'] = 400;
        $response['body'] = json_encode([
            'error' => 'Username Exist'
        ]);
        return $response;
    }
}