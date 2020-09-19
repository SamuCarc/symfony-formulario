<?php

namespace App\Controller;

use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    /**
     * @Route("/", name="users")
     */
    public function index(Request $request)
    {
        $mensaje = "";
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Users::class);
        
        // Comprobamos si se ha echo post al formulario
        if (isset($_POST['email']) && isset($_POST['token']) && $_POST['email']) {
            $token = $_POST['token'];
            $action = $_POST['action'];
             
            // Comprobamos si pasa el recaptcha
            if($this->validarRecaptcha($token, $action)) {
                
                $nom = $_POST['nombre'];
                $ape = $_POST['apellidos'];
                $em = $_POST['email'];
                $tel = $_POST['telefono'];

                // Comprobamos si existe el usuario
                $usuarioExiste = $repository->findOneBy(['email' => $em]);
                if (!$usuarioExiste) {
                    // No existe: Por lo tanto creamos
                    $user = new Users($nom,$ape,$em,$tel);
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $mensaje = "Usuario Creado: ". $em;
                } else {
                    // Existe: Creamos
                    $usuarioExiste->setNombre($nom);
                    $usuarioExiste->setApellidos($ape);
                    $usuarioExiste->setEmail($em);
                    $usuarioExiste->setTelefono($tel);
                    $entityManager->flush();
                    $mensaje = "Usuario Editado: ". $em;
                }
            } 
            // No pasa el recaptcha
            else {
                $mensaje = 'No has pasado el recaptcha';
            }
        }

        // Borrar usuario
        if (isset($_POST['delete-email']) && $_POST['delete-email']) {
            $userDelete = $repository->findOneBy(['email' => $_POST['delete-email']]);
            if ($userDelete) {
                $entityManager->remove($userDelete);
                $entityManager->flush();
                $mensaje = "Usuario ". $_POST['delete-email']. " borrado";
            } else {
                $mensaje = "El usuario que ha intentado borrar no existe";
            }
        }
        $todosUsuarios = $this->obtenerTodosUsuarios($repository);

        return $this->render('users/index.html.twig', [
            'controller_name' => 'UsersController',
            'mensaje' => $mensaje,
            'todosUsuarios' => $todosUsuarios
        ]);

    }
    

    // Obtenemos todos los usuarios para mostrar en HTML
    public function obtenerTodosUsuarios ($repository) {
        $allUsers = $repository->findAll();
        $arrayUsers = [];
        $i = 0;
        foreach ($allUsers as $row) {
            $nombreApellidos = $row->getNombre()." ".$row->getApellidos();
            $arrayUsers[$i]["content"] = $row->getEmail()." (". $nombreApellidos .")"; 
            $arrayUsers[$i]["id"] = $row->getId(); 
            $arrayUsers[$i]["email"] = $row->getEmail(); 
            $i++;
        }
        return $arrayUsers;
    }

    // Validamos el token traido del Recaptcha
    public function validarRecaptcha ($token, $action) {
        // Hacemos un POST con nuestra api_key
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => '6Lcj880ZAAAAAFqQB_-1TzQmyjYSiQLXMAruHUqj', 'response' => $token)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $arrResponse = json_decode($response, true);
        // Comprobamos si pasa el recaptcha
        if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
            return true;
        } else {
            return false;
        }
    }
}
