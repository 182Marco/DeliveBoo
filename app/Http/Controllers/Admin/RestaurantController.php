<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; //validation
use Illuminate\Support\Facades\Storage; //storage
use App\Restaurant;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // get authenticated user restaurant by comparing 
        // his id in UserTable with same Fk in restaurants table
        $auth_restaurants = Restaurant::where('user_id', auth()->id())->get();   

        return view('admin.restaurants.index', compact('auth_restaurants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {


        return view ('admin.restaurants.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validation
        $request-> validate([
            'name'=> 'required|max:80',
            'phone'=> 'required',
            'city'=> 'required',
            'address'=> 'required',
            'img' =>'nullable|mimes:jpg,png,jpeg,webp'
        ],
        [
            'required'=> 'The :attribute is required!',
            'unique' => 'The :attribute is already used',
            'max' => 'Max :max characters allowed for the :attribute'
        ]
    
    );

        $data=$request->all();

         //Add Cover Image ( if exists)
         if(array_key_exists('img', $data)) {
            $img_path=Storage::put('restaurants-imgs', $data['img']);//'restaurants-imgs' non so se funzicherà

            //overwrite img with file path
            $data['img']=$img_path; //Url
        }

        //create e save records on db
        $new_restaurant=new Restaurant();

        //fillable in Restaurant
        $new_restaurant->fill($data);

        //IL SANTO GRAAL
        $new_restaurant->user_id=auth()->id();

        //save
        $new_restaurant->save();

        return redirect()->route('admin.restaurants.show', $new_restaurant->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     */
    public function show($id)
    {
        //dump($id);
        //single restaurant
        $restaurant= Restaurant::find($id);

        if(! $restaurant) {
            abort(404);
        }
        elseif(! $restaurant->user_id == auth()->id()) {
            return 'this restaurant doesn\'t belongs to you!';
        }

        return view('admin.restaurants.show', compact('restaurant'));
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //dd($id);
        //edit post
        $restaurant= Restaurant::find($id);
        //dd($restaurant);

        if(! $restaurant) {
            abort(404);
        }

        return view('admin.restaurants.edit', compact('restaurant'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //validation
        $request->validate([
            'name'=> [
                'required',
                Rule::unique('restaurants')->ignore($id),
                'max:80'
            ],

            'phone'=> 'required',
            'city'=> 'required',
            'address'=> 'required',
            'img' => 'nullable|mimes:jpg,png,jpeg,webp'
        ],
        
        [
            'required'=> 'The :attribute is required!',
            'unique'=> 'This :attribute is already used',
            'max' => 'you have exceeded the :max characters allowed for the :attribute'
        ]);

        $data= $request->all();

        $restaurant = Restaurant::find($id);

        //Image update
        if (array_key_exists('img', $data)) {
            //delete previous
            if ($restaurant->img) {
                Storage::delete($restaurant->img);
            }
            //set new one
            $data['img']= Storage::put('restaurants-imgs', $data['img']);
        }

        $restaurant->update($data); //fillable
        return redirect()->route('admin.restaurants.show', $restaurant->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $restaurant= Restaurant::find($id);

         //Removal Image
         if ($restaurant->img) {
            Storage::delete($restaurant->img);
        }


        //delete posts
        $restaurant->delete();

        return redirect()->route('admin.restaurants.index')->with('deleted', $restaurant->name);
    }
}

