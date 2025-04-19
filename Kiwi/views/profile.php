<h1>{{username}}</h1>
<h1>{{page}}</h1>


@component('header', {"id": 1,"pass":123,"username": "pepa"})
@component('header', {
    "id": 1,
    "pass":123,
    "username":"asd"
})

{{users}}
@loop(users as $user)
<p>{{user}}</p>
@endloop
