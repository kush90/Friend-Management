<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Friend;
use App\User;
use App\Subscribe;
use DB;
class FriendController extends Controller
{
    public function make_friend(Request $request){
        
        $friends = json_decode($request->getContent(), true);
        
       
        $users_in_db=[];

        // find the record whether the request's friends'email are in user table or not
        // if it is not in user table, create new user and push that user's id in array,'users_in_db'
        // if it is in user table , push that user's id in array,'users_in_db'
        for($i=0;$i<count($friends["friends"]);$i++){
            $user  = User::where('email',$friends["friends"][$i])->first();
            
            if(empty($user))
            {
                $name =  substr($friends["friends"][$i], 0, strpos($friends["friends"][$i], '@'));
                $email = $friends["friends"][$i];
                $password = 12345;
                $user = User::create(['name'=>$name,'email'=>$email,'password'=>$password]);
                array_push($users_in_db,$user->id);
                
                
            }
            else{
                array_push($users_in_db,$user->id);
            }
            
            
        }
        /* ---------------- End --------------- */

        // Check the two given emails have a friend connection or not in friends table 


        $friends = DB::table('friends')
        ->whereIn('first_user',$users_in_db)
        ->whereIn('second_user',$users_in_db)
        ->where('friend',1)
        ->first();

        if(empty($friends)){
            Friend::Create([
                'first_user'=>$users_in_db[0],
                'second_user'=>$users_in_db[1],
                'friend'=>1
            ]);

            return response()->json(['success' => true]);
        }
        else{
            return response()->json([
                'success'=> false,
                'message'=>'The two given emails have already a friend connection in the system'
                ]);
        }

        /*  ----------- End -----------------  */ 
        
        
    }

    public function friend_lists(Request $request){

        $email = json_decode($request->getContent(), true);
        $user = User::where('email',$email['email'])->first();
        // check whether the given email is in user table or not
        if(empty($user)){
            return response()->json([
                'success'=> false,
                'message'=>'The given email is not found in the system'
                ]);
        }
        // if it is in user table, search list of friends of that email in friends table
               
        $friend_lists =[];
        $friends=Friend::where('first_user',$user->id)
        ->orWhere('second_user',$user->id)
        ->where('friend',1)->get();
        foreach($friends as $friend) {
            
            if($friend->first_user == $user->id ){
                array_push($friend_lists,$friend->secondUser->email);
            }
            else{
                array_push($friend_lists,$friend->firstUser->email);
            }
            
        }
        return response()->json(
            [
                'success'=>true,
                'friends'=> $friend_lists,
                'count'=>count($friend_lists)
            ]);

        /* ---- End -----------*/
        
    }

    public function common_friend_lists(Request $request){

        $friends = json_decode($request->getContent(), true);

        // check the email whether the email is in user table or not
        $users = User::WhereIn('email',$friends["friends"])->get();
        if(count($friends["friends"])!= count($users)){
            return response()->json([
                'success'=> false,
                'message'=>'One of the given email is not found in the system'
                ]);
        }
        
        // get the first email of the friend connection lists
        $first_lists=Friend::where('first_user',$users[0]->id)
                        ->orWhere('second_user',$users[0]->id)
                        ->where('friend',1)
                        ->get();

        // get the second email of the friend connection lists
        $second_lists=Friend::where('first_user',$users[1]->id)
                        ->orWhere('second_user',$users[1]->id)
                        ->where('friend',1)
                        ->get();
        
        //  get the first email of the friend lists              
        $firsts = [];
        foreach($first_lists as $first){
            if($first->first_user == $users[0]->id && $first->second_user !==$users[0]->id){
                array_push($firsts,$first->secondUser);
             }
            else{
                array_push($firsts,$first->firstUser);
            }
                           
        }

        //  get the second email of the friend lists
        $seconds = [];
        foreach($second_lists as $second){

            if($second->first_user == $users[1]->id && $second->second_user !==$users[1]->id){
                    array_push($seconds,$second->secondUser);
            }
            else{
                    array_push($seconds,$second->firstUser);
            }       
        }
       

        // compare the the first and second email of the friend lists
       $results;
       if(count($firsts)>count($seconds)){
        $results = array_intersect($firsts,$seconds);
       }
       else{
        $results = array_intersect($seconds,$firsts);
       }
       
       // get the email of the compared friend lists
       $common=[];
       foreach($results as $result){
           array_push($common,$result->email);
       }


        return response()->json(
            [
                "success"=>true,
                "friends"=>$common,
                "count"=>count($common)

            ]
        );
       
    }

    public function subscribe(Request $request){
        $friends = json_decode($request->getContent(), true);

        
        // check the email whether the email is in user table or not
        $requestor = User::Where('email',$friends["requestor"])->first();
        $target = User::where('email',$friends["target"])->first();
        if(empty($requestor) || empty($target)){
            return response()->json([
                'success'=> false,
                'message'=>'One of the given email is not found in the system'
                ]);
        }

        Subscribe::create([
            "requestor"=>$requestor->id,
            "target"=>$target->id,
            "subscribe"=>1
        ]);

        return response()->json([
            'success'=>true,
        ]);

        
        

        
        
    }

}
