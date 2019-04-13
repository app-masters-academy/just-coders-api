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

GET `http://api.justcoders.appmasters.io/{env}/post`

# Development

- [JWT authentication for Lumen 5.6](https://medium.com/tech-tajawal/jwt-authentication-for-lumen-5-6-2376fd38d454)
