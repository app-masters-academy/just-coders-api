# Just Coders API

API written to be used on [Just Coders Network](https://github.com/app-masters-academy/just-coders-network/).  


## Routes

API Base URL: http://api.justcoders.appmasters.io/

You must choose a environment to work on, like `http://api.justcoders.appmasters.io/dev/user` 

### Auth

POST `http://api.justcoders.appmasters.io/{env}/auth/loginsocial` with body containing:

``` 
{
	"email": "james@appmaster.io", 
	"name": "James Oliveira",
	"network": "github",
	"id": "484848",
	"photo" : "http://www.tiagogouvea.com/eu.jpg"
}
```

### Posts 

POST `http://api.justcoders.appmasters.io/{env}/post` sending just `content` attribute: 
```
{"content":"I'm happy to be here"} 
```

### Timeline

GET `http://api.justcoders.appmasters.io/{env}/timeline` will return: 
```json
{
    "data": [
        {
            "user_id": 2,
            "content": "I'm happy to be here",
            "likes": 0,
            "id": 1,
            "created_at": "2019-04-13 10:42:46",
            "user": {
                "name": "Great Admin",
                "thumb_url": "http://www.tiago.com/eu.jpg"
            }
        },
        {
            "user_id": 1,
            "content": "Hello World",
            "likes": 1,
            "id": 2,
            "created_at": "2019-04-13 10:41:41",
            "user": {
                "name": "Linus Torvalds",
                "thumb_url": "http://im.linus.ow/self.png"
            }
        }
    ],
    "count": 2
}
```


### Like

POST `http://api.justcoders.appmasters.io/{env}/post/{id}/like` sending an empty body, will return updated post: 
```json
{
    "data": {
       "user_id": 2,
       "content": "I'm happy to be here",
       "likes": 2,
       "id": 1,
       "created_at": "2019-04-13 10:42:46",
       "user": {
           "name": "Great Admin",
           "thumb_url": "http://www.tiago.com/eu.jpg"
       }
    }
}
```


# Development

- [JWT authentication for Lumen 5.6](https://medium.com/tech-tajawal/jwt-authentication-for-lumen-5-6-2376fd38d454)
