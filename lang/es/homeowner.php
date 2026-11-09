<?php

return [

    'nav' => [
        'my_projects' => 'Mis proyectos',
        'login' => 'Iniciar sesión',
        'logout' => 'Salir',
    ],

    'register' => [
        'tagline' => 'Gratis para dueños de casa',
        'heading' => 'Publica tu proyecto',
        'subheading' => 'Crea tu cuenta y recibe respuestas de contratistas de tu zona.',
        'submit' => 'Crear mi cuenta',
        'submitting' => 'Creando…',
        'have_account' => '¿Ya tienes cuenta?',
    ],

    'login' => [
        'heading' => 'Bienvenido de nuevo',
        'subheading' => 'Entra para ver tus proyectos.',
        'remember' => 'Recordarme',
        'submit' => 'Entrar',
        'no_account' => '¿No tienes cuenta?',
        'create_account' => 'Regístrate gratis',
    ],

    'fields' => [
        'name' => 'Tu nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'phone_hint' => 'Solo lo verán los contratistas que tú aceptes.',
        'city' => 'Ciudad',
        'state' => 'Estado',
        'zip' => 'Código postal',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirma tu contraseña',
        'title' => '¿Qué necesitas?',
        'title_placeholder' => 'Ej. Cambiar el techo de mi casa',
        'subcategory' => 'Tipo de trabajo',
        'subcategory_placeholder' => 'Elige una categoría',
        'description' => 'Describe tu proyecto',
        'description_placeholder' => 'Cuéntales a los contratistas qué hay que hacer, medidas, materiales, etc.',
        'urgency' => '¿Para cuándo?',
        'budget' => 'Presupuesto aproximado',
        'budget_placeholder' => 'Elige un rango',
        'optional' => 'opcional',
        'location' => 'Ubicación',
    ],

    'dashboard' => [
        'greeting' => 'Hola, :name',
        'heading' => 'Mis proyectos',
        'new_project' => 'Publicar proyecto',
        'empty_title' => 'Aún no tienes proyectos',
        'empty_body' => 'Publica lo que necesitas y los contratistas de tu zona te responderán.',
        'interested_count' => '{0} Aún sin contratistas interesados|{1} 1 contratista interesado|[2,*] :count contratistas interesados',
        'new_count' => ':count nuevo|:count nuevos',
    ],

    'form' => [
        'back' => 'Volver',
        'create_heading' => 'Publica tu proyecto',
        'create_subheading' => 'Es gratis. Tus datos de contacto solo los verán los contratistas que tú aceptes.',
        'edit_heading' => 'Editar proyecto',
        'publish' => 'Publicar proyecto',
        'save' => 'Guardar cambios',
        'created' => '¡Tu proyecto ya está publicado! Te avisaremos cuando un contratista se interese.',
        'updated' => 'Proyecto actualizado.',
    ],

    'show' => [
        'edit' => 'Editar',
        'mark_in_progress' => 'Marcar en progreso',
        'mark_closed' => 'Cerrar proyecto',
        'close_confirm' => '¿Cerrar este proyecto? Los contratistas ya no lo verán.',
        'reopen' => 'Volver a abrir',
        'hidden_notice' => 'Este proyecto fue ocultado por el equipo de Workon. Contáctanos si crees que es un error.',
        'interested_heading' => 'Contratistas interesados',
        'no_interests' => 'Todavía no hay contratistas interesados. Te avisaremos por correo cuando alguien responda.',
        'view_page' => 'Ver su página',
        'accept' => 'Aceptar',
        'decline' => 'Rechazar',
        'accepted_hint' => 'Ya puede ver tus datos de contacto.',
    ],

    'status' => [
        'open' => 'Abierto',
        'in_progress' => 'En progreso',
        'closed' => 'Cerrado',
    ],

    'urgency' => [
        'urgent' => 'Urgente',
        'this_month' => 'Este mes',
        'flexible' => 'Solo cotizando',
    ],

    'budget' => [
        'under_1k' => 'Menos de $1,000',
        '1k_5k' => '$1,000 – $5,000',
        '5k_15k' => '$5,000 – $15,000',
        'over_15k' => 'Más de $15,000',
        'not_sure' => 'No estoy seguro',
    ],

    'interest_status' => [
        'pending' => 'Nuevo',
        'accepted' => 'Aceptado',
        'declined' => 'Rechazado',
    ],

];
