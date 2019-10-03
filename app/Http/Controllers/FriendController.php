<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Friend;
use App\User;
use App\Subscribe;
use App\Block;
use App\Message;
use DB;
class FriendController extends Controller
{
    // make a friend connection
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

        // Check the two given email have blocked each other or not
        $blocks = DB::table('blocks')
        ->whereIn('requestor',$users_in_db)
        ->whereIn('target',$users_in_db)
        ->where('block',1)
        ->first();

        if(!empty($blocks)){
            return response()->json([
                'success'=> false,
                'message'=>'The two given emails have already block each other in the system'
                ]);
        }

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

    // get friend lists by requested email
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

    // get friend lists of between two requested emails
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

        /* get two friend of the lists from one query*/
        $results = DB::table('friends')
                     ->select('*')
                     ->where('first_user', '=', $users[0]->id)
                     ->orWhere('second_user','=',$users[0]->id)
                     ->where('friend',1)
                     ->union(
                        DB::table('friends')
                        ->select('*')
                        ->where('first_user', '=', $users[1]->id)
                        ->orWhere('second_user','=',$users[1]->id)
                        ->where('friend',1)
                     )
                     ->get();

        
        
        $firsts=[];
        $seconds=[];
        foreach($results as $result){
            if($result->first_user == $users[0]->id && $result->second_user != $users[0]->id){
                array_push($firsts,$result->second_user);
             }
            if($result->first_user != $users[0]->id && $result->second_user == $users[0]->id){
                array_push($firsts,$result->first_user);
            }
            if($result->first_user == $users[1]->id && $result->second_user != $users[1]->id){
                array_push($seconds,$result->second_user);
             }
            if($result->first_user != $users[1]->id && $result->second_user == $users[1]->id){
                array_push($seconds,$result->first_user);
            }
        }

        // compare the the first and second email of the friend lists
        $common;
        if(count($firsts)>count($seconds)){
         $common = array_intersect($firsts,$seconds);
        }
        else{
         $common = array_intersect($seconds,$firsts);
        }

        $common_emails = User::select('email')->WhereIn('id',$common)->get();
        

        return response()->json(
            [
                "success"=>true,
                "friends"=>$common_emails,
                "count"=>count($common_emails)  
            ]
        );

       
    }

    // subscribe to a target email by requested email
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

        $subscribe = Subscribe::where("requestor",'=',$requestor->id)
                ->where("target",'=',$target->id)
                ->where("subscribe",'=',1)->first();

        // check whether the subscribtion is already make or not by the requested email
        if(!empty($subscribe)){
            return response()->json([
                'success'=> false,
                'message'=>'You are already subscribed to this email'
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

    // block the target email by requested email
    public function block(Request $request){
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

        $block = Block::where("requestor",'=',$requestor->id)
                ->where("target",'=',$target->id)
                ->where('block','=',1)
                ->first();

        // check whether the block is already make or not by the requested email
        if(!empty($block)){
            return response()->json([
                'success'=> false,
                'message'=>'You are already block to this email'
                ]);
        }
        
        Block::create([
            "requestor"=>$requestor->id,
            "target"=>$target->id,
            "block"=>1
        ]);

        return response()->json([
            'success'=>true,
        ]);        
    }

    //retrieve all email addresses that can receive updates from an email address
    public function email_lists(Request $request){
        $email = json_decode($request->getContent(), true);

        // check the email whether the email is in user table or not
        $sender = User::where('email',$email["sender"])->first();
        if(empty($sender)){
            return response()->json([
                'success'=>false,
                'message'=> "The given email is not found in the System"
             ]);

        }
        // create message  send by requested email
        Message::create([
            'sender'=>$sender->id,
            'text'=>$email["text"]
        ]);

        // get the friend list of the requested email
        $friends=Friend::where('first_user',$sender->id)
        ->orWhere('second_user',$sender->id)
        ->where('friend',1)->get();

        $friend_lists = [];
        foreach($friends as $friend){

            if($friend->first_user == $sender->id && $friend->second_user != $sender->id){
                    array_push($friend_lists,$friend->secondUser);
            }
            else{
                    array_push($friend_lists,$friend->firstUser);
            }       
        }
        

        
        $email_lists=[];
        foreach($friend_lists as $friend){

            // check whether the friend lists is blocked by the requestd email or not
            $block = Block::where('requestor','=',$sender->id)
            ->where('target','=',$friend->id)
            ->where('block','=',1)
            ->first();
            // if not blocked,get all the subscribtion email lists from the friend lists of the requested email 
            if(empty($block)){
                $subscribe = Subscribe::where('requestor','=',$sender->id)
                ->where('target','=',$friend->id)
                ->where('subscribe','=',1)
                ->first();
               
               array_push($email_lists,$friend->email);
            }
              
        }
        if(empty($email_lists)){
            return response()->json([
                'success'=> false,
                'message'=> "There is no email lists"
            ]);
        }
        return response()->json([
            'success'=>true,
            'recipients'=>$email_lists
        ]);
       




        
    }

}
