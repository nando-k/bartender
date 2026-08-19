# bartender
backend para sistema del bar tradicion

todas las rutas tienen este formato

GET     /api/almacen/unidades-medida
POST    /api/almacen/unidades-medida
GET     /api/almacen/unidades-medida/{idUnidadMedida}
PUT     /api/almacen/unidades-medida/{idUnidadMedida}
PATCH   /api/almacen/unidades-medida/{idUnidadMedida}
DELETE  /api/almacen/unidades-medida/{idUnidadMedida}
POST    /api/almacen/unidades-medida/{idUnidadMedida}/restore

se envían de este modo
{
    "idPersona": 1,
    "cuenta": "admin",
    "passwordHash": "MiPasswordSegura",
    "activo": true
}

y las respuestas son:
Intentar:

{
    "numeroDocumento": "1234567",
    "complemento": null,
    "sexo": "M",
    "fechaNacimiento": "1990-05-10",
    "nombres": "JUAN"
}

devuelve:

{
    "success": false,
    "message": "La persona ya se encuentra registrada.",
    "errors": {
        "numeroDocumento": [
            "Ya existe una persona con el mismo número de documento y complemento."
        ]
    }
}

Pero esto sí sería válido:

{
    "numeroDocumento": "1234567",
    "complemento": "1",
    "sexo": "M",
    "fechaNacimiento": "1990-05-10",
    "nombres": "JUAN"
}

Y también:

{
    "numeroDocumento": "1234567",
    "complemento": "2",
    "sexo": "F",
    "fechaNacimiento": "1990-05-10",
    "nombres": "MARIA"
}
