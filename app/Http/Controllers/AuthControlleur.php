<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;

class AuthControlleur extends Controller
{
    protected $auth;
    
    //
    public function __construct()
    {
        $firebase = (new Factory)
        ->withServiceAccount(base_path('storage\firebase\firebase-auth.json'));
       /*  ->withDatabaseUri('https://hackonifri-default-rtdb.firebaseio.com/');
        $database=$firebase->createDatabase();
        $reference = $database->getReference('users');
        $reference->set(['connection' => true]);
        $snapShot = $reference->getSnapshot();
        $value = $snapShot->getValue(); */
        $this->auth = $firebase->createAuth();

    }
    // ✅ Inscription
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'matricule'=>'required|string|unique:users',
            'annee'=>'required|integer',
            'filiere'=>'required|string',
            'is_responsable' => 'required|boolean',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $data =[ 
                'nom'=> $request->nom, 
                'prenom'=> $request->prenom, 
                'matricule'=> $request->matricule, 
                'annee'=> $request->annee, 
                'filiere'=> $request->filiere, 
                'is_responsable'=> $request->is_responsable,
                'email'=> $request->email, 
                'password'=> $request->password, 
                'emailVerified'=> false,
            
            ];
            // Création du compte utilisateur Firebase
            $user = $this->auth->createUser($data);
            $data['firebase_uid'] = $user->uid;  
            // Enregistrement du user dans la base de données locale
             User::create($data);          
            return response()->json([
                'message' => 'Utilisateur créé avec succès !',
                'uid' => $user->uid
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur d’inscription : ' . $e->getMessage()], 500);
        }
    }

    // ✅ Connexion
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idToken' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
           // $token = $request->input('idToken');
            $user = null;
            // Vérifier si le token ID est fourni            
            if (!$request->input('idToken')) {
                // Vérifier si l'email et le mot de passe sont fournis
                if (!$request->input('email') ||!$request->input('password')) {
                    return response()->json(['error' => 'Email et mot de passe requis'], 400);
                }

                $firebase_user = $this->auth->signInWithEmailAndPassword($request->input('email'), $request->input('password'));

                // Vérifier si l'utilisateur existe dans la base de données locale
                $user = User::where('email', $request->input('email'))->first();
                //$token = $firebase_user->idToken();
            }else {
                // Vérification du token ID
                $verifiedIdToken = $this->auth->verifyIdToken($request->input('idToken'));
                $uid = $verifiedIdToken->claims()->get('sub');

                $firebaseUser = $this->auth->getUser($uid);

                // Vérifier si l'utilisateur existe dans la base de données locale
                $user = User::where('firebase_uid', $uid)->first();
            }

            if (!$user) {
                return response()->json(['error' => 'Utilisateur non trouvé'], 404);
            }

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => $user,
                'token' => $user->createToken('API Token')->plainTextToken
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalide ou expiré. ' . $e->getMessage()], 401);
        }
        
    }

    // ✅ Déconnexion
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->getClaim('sub');

            $this->auth->revokeRefreshTokens($uid);

            return response()->json(['message' => 'Déconnexion réussie. Token révoqué.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalide ou expiré.'], 401);
        }
    }

    public function profile()
    {
       //récupération des informations de l'utilisateur connecté
       $user = auth()->user();

       if (!$user) {
           return response()->json(['error' => 'Utilisateur non connecté'], 401);
       }

       return response()->json($user, 200);
    }
    // ✅ Récupération du token d'authentification
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->getClaim('sub');

            $newToken = $this->auth->createCustomToken($uid);

            return response()->json([
                'token' => $newToken,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalide ou expiré. ' . $e->getMessage()], 401);
        }
    }
    // ✅ Récupération des informations d'un utilisateur
    public function getUser(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->getClaim('sub');

            $user = $this->auth->getUser($uid);

            return response()->json([
                'user' => [
                    'uid' => $user->uid,
                    'nom' => $user->displayName?? '',
                    'email' => $user->email,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalide ou expiré.'], 401);
        }
    }
  
}
