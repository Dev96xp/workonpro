<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones — Workon</title>
    @vite('resources/css/welcome.css')
</head>
<body class="min-h-screen bg-zinc-950 px-6 py-16 text-zinc-300 antialiased sm:px-10">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('home') }}" class="text-2xl font-black tracking-tighter text-yellow-400">Workon</a>

        <h1 class="mt-8 text-3xl font-black tracking-tight text-white sm:text-4xl">Términos y Condiciones de Servicio</h1>
        <p class="mt-2 text-sm text-zinc-500">Última actualización: {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>
        <p class="mt-4 text-sm leading-relaxed text-zinc-500">Los resúmenes en <strong class="text-zinc-300">"Lo que significa"</strong> son solo una guía de lectura rápida y no tienen valor legal por sí mismos — el texto completo de cada sección es lo que aplica.</p>

        <div class="prose prose-invert mt-10 max-w-none space-y-10 text-zinc-300 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-yellow-400 [&_p]:leading-relaxed [&_p]:text-zinc-400 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6 [&_ul]:text-zinc-400">

            <section>
                <h2>1. Aceptación de los Términos</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> al usar Workon aceptas estas reglas. Si no estás de acuerdo, no uses la Plataforma.</p>
                <p>Al crear una cuenta, registrar un negocio o usar de cualquier forma la plataforma Workon ("Workon", "nosotros", "la Plataforma"), operada por [RAZÓN SOCIAL], aceptas quedar obligado por estos Términos y Condiciones ("Términos") y por nuestra <a href="{{ route('privacy') }}" class="text-yellow-400 hover:underline">Política de Privacidad</a>. Si no estás de acuerdo, no debes usar la Plataforma.</p>
            </section>

            <section>
                <h2>2. Descripción del Servicio</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> te damos las herramientas (sitio web, reservas, cobros, empleados). Lo que vendas o el servicio que prestes a tus clientes es entre tú y ellos — nosotros no somos parte de esa relación.</p>
                <p>Workon es una plataforma SaaS que permite a negocios crear y administrar:</p>
                <ul>
                    <li>Un sitio web público propio con subdominio (ej. tunegocio.workonpro.com), galería, servicios y cupones.</li>
                    <li>Un sistema de reservas y citas para sus clientes.</li>
                    <li>Facturación y cobro a sus propios clientes.</li>
                    <li>Gestión de empleados, incluyendo registro de entrada/salida (clock-in/clock-out) con verificación opcional de ubicación (geolocalización).</li>
                    <li>Una tarjeta de presentación digital ("Workon Card") y presencia en el directorio central de búsqueda de Workon.</li>
                </ul>
                <p>Workon actúa únicamente como proveedor de la herramienta tecnológica y <strong>no es un mercado ni intermediario ("marketplace")</strong> de las transacciones entre tú y tus clientes. Cada Tenant es el único responsable ("merchant of record") frente a sus propios clientes por los servicios que ofrece, sus precios, políticas de reembolso, y el cumplimiento de la ley aplicable a su negocio. Workon no garantiza la calidad, legalidad o cumplimiento de los servicios que cada Tenant ofrezca a través de la Plataforma.</p>
            </section>

            <section>
                <h2>3. Registro de Cuenta y Responsabilidades</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> tú controlas tu cuenta y respondes por lo que hagan tus empleados dentro de ella. Danos datos reales al registrarte.</p>
                <p>Eres responsable de mantener la confidencialidad de tus credenciales de acceso y de toda actividad que ocurra bajo tu cuenta. Debes proporcionar información veraz al registrarte. Eres el único responsable del contenido que publiques en tu página (textos, fotos, precios, políticas) y de tu relación con tus propios clientes.</p>
                <p>La cuenta principal (el dueño del negocio, "Cuenta Principal") puede crear cuentas adicionales para sus empleados dentro del módulo de asistencia. La Cuenta Principal es responsable de: (a) que sus empleados cumplan estos Términos al usar la Plataforma, y (b) cualquier incumplimiento de estos Términos por parte de dichos empleados.</p>
            </section>

            <section>
                <h2>4. Planes, Suscripciones y Pagos</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> tu suscripción se cobra automáticamente hasta que la canceles. Si un cobro falla, podemos suspender tu cuenta.</p>
                <p>Los pagos se procesan a través de Stripe. Al suscribirte a un plan de pago, autorizas cargos recurrentes automáticos según la periodicidad de tu plan, hasta que canceles tu suscripción. Podemos modificar los precios de los planes con aviso previo razonable; los cambios no aplican de forma retroactiva al periodo ya pagado.</p>
                <p><strong>Política de reembolsos:</strong> [DEFINIR POLÍTICA DE REEMBOLSOS — ej. "no se otorgan reembolsos por periodos parciales ya iniciados" o "reembolso completo dentro de los primeros X días"].</p>
                <p>Si no podemos procesar un cobro con tu método de pago, podemos suspender el acceso a las funciones de pago de tu cuenta hasta que el pago se regularice, sin que esto te exima de las cantidades adeudadas. Eres responsable de cualquier impuesto aplicable a tu propio negocio derivado del uso de la Plataforma.</p>
            </section>

            <section>
                <h2>5. Uso Aceptable</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> no uses Workon para hacer algo ilegal, spamear gente, o intentar hackear la plataforma. Si lo haces, podemos cerrar tu cuenta.</p>
                <p>No puedes usar Workon para:</p>
                <ul>
                    <li>Actividades ilegales, fraudulentas o que infrinjan derechos de terceros.</li>
                    <li>Enviar spam o contactar clientes sin su consentimiento.</li>
                    <li>Intentar vulnerar la seguridad de la Plataforma o acceder a datos de otros Tenants.</li>
                    <li>Suplantar la identidad de otra persona o negocio.</li>
                    <li>Usar robots, scrapers u otros medios automatizados para extraer información de la Plataforma o del directorio de búsqueda.</li>
                </ul>
                <p>Nos reservamos el derecho de suspender o cancelar cuentas que violen esta política, sin previo aviso en casos graves.</p>
            </section>

            <section>
                <h2>6. Derechos de Workon sobre la Plataforma</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> podemos cambiar o mejorar la plataforma cuando queramos, y quitar contenido que rompa las reglas. También podemos ofrecerle Workon a negocios que compitan contigo.</p>
                <p>Podemos modificar, agregar o quitar funciones de la Plataforma en cualquier momento. No todas las funciones estarán disponibles para todos los planes o en todo momento. Podemos, a nuestra discreción, rechazar o remover cualquier contenido ("Materiales", definido en la Sección 7) que consideremos que viola estos Términos.</p>
                <p>Ofrecer Workon a otros negocios, incluyendo posibles competidores tuyos, no constituye una violación de estos Términos ni nos obliga a exclusividad contigo.</p>
            </section>

            <section>
                <h2>7. Licencia sobre tus Materiales</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> las fotos e información de tu negocio siguen siendo tuyas. Nos das permiso de mostrarlas (en tu página y en el buscador de Workon) para poder operar el servicio.</p>
                <p>"Materiales" significa el nombre de tu negocio, tu marca, fotos, videos, textos, precios, servicios, cupones y cualquier otro contenido que subas o proporciones a través de la Plataforma. No adquirimos la propiedad de tus Materiales; conservas todos los derechos sobre ellos.</p>
                <p>Nos otorgas una licencia no exclusiva, mundial y libre de regalías para alojar, usar, mostrar, reproducir y distribuir tus Materiales, únicamente con el fin de operar, proveer y promocionar la Plataforma — por ejemplo, mostrar tu negocio en tu página pública, en el directorio central de búsqueda de Workon, o en material de marketing de Workon. Esta licencia termina cuando eliminas el contenido correspondiente o cierras tu cuenta, salvo por copias ya distribuidas que retiraremos dentro de un plazo razonable.</p>
            </section>

            <section>
                <h2>8. Propiedad y Uso de tus Datos</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> los datos de tus clientes y empleados son tuyos. Nosotros solo los guardamos para que la plataforma funcione.</p>
                <p>Tú (el Tenant) conservas la propiedad de los datos de tu negocio, tus clientes y tus empleados que ingreses a la Plataforma. Nos otorgas una licencia limitada para almacenar, procesar y mostrar esa información únicamente con el fin de operar el servicio que contrataste. Consulta nuestra <a href="{{ route('privacy') }}" class="text-yellow-400 hover:underline">Política de Privacidad</a> para más detalle sobre cómo tratamos los datos.</p>
            </section>

            <section>
                <h2>9. Geolocalización de Empleados</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> si activas la ubicación GPS para el registro de entrada/salida, es tu responsabilidad avisarle a tus empleados y cumplir la ley laboral de tu lugar — no la nuestra.</p>
                <p>La función de registro de entrada/salida puede solicitar la ubicación GPS del dispositivo del empleado para verificar su presencia dentro de un radio configurado por el negocio. <strong>Es tu responsabilidad como Tenant obtener el consentimiento de tus empleados antes de activar esta función</strong>, informarles claramente de su uso, y cumplir con la legislación laboral y de protección de datos aplicable en tu jurisdicción. Workon no supervisa ni controla cómo cada Tenant configura o comunica esta función a sus empleados, y no asume responsabilidad por el incumplimiento de dichas obligaciones por parte del Tenant.</p>
            </section>

            <section>
                <h2>10. Propiedad Intelectual de Workon</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> el software y la marca Workon son nuestros. No puedes usar nuestro nombre/logo, ni copiar la plataforma.</p>
                <p>El software, diseño, marca y logotipo de Workon son propiedad de [RAZÓN SOCIAL] y están protegidos por leyes de propiedad intelectual. Estos Términos no te otorgan ningún derecho sobre la marca o el software de Workon, más allá del uso permitido de la Plataforma. No puedes usar la marca "Workon" para tu propio negocio, ni intentar copiar, descompilar o realizar ingeniería inversa de la Plataforma.</p>
            </section>

            <section>
                <h2>11. Disponibilidad del Servicio y Terceros</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> no prometemos que la plataforma nunca falle — dependemos de proveedores externos como Stripe.</p>
                <p>Nos esforzamos por mantener la Plataforma disponible, pero no garantizamos un servicio ininterrumpido o libre de errores. Dependemos de proveedores externos (procesamiento de pagos, hosting, servicios de geolocalización) cuyas interrupciones están fuera de nuestro control y no generan responsabilidad para Workon.</p>
            </section>

            <section>
                <h2>12. Limitación de Responsabilidad</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> si algo sale mal, nuestra responsabilidad tiene un tope — y no respondemos por problemas entre tú y tus propios clientes o empleados.</p>
                <p>En la máxima medida permitida por la ley, la Plataforma se ofrece "tal cual" y "según disponibilidad", sin garantías de ningún tipo. Workon no será responsable por daños indirectos, incidentales, especiales o consecuentes (incluyendo pérdida de ingresos, clientes o datos) derivados del uso o la imposibilidad de uso de la Plataforma. Nuestra responsabilidad total frente a ti, por cualquier motivo, no excederá el monto que hayas pagado a Workon en los últimos tres (3) meses.</p>
                <p>Workon no es responsable por disputas, daños o reclamos que surjan entre un Tenant y sus propios clientes o empleados como resultado de los servicios que el Tenant ofrece a través de la Plataforma, incluyendo reembolsos, cancelaciones, o el cumplimiento de leyes de protección al consumidor aplicables al negocio del Tenant.</p>
            </section>

            <section>
                <h2>13. Indemnización</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> si alguien nos demanda por algo que tú hiciste con tu cuenta, tú cubres esos costos, no nosotros.</p>
                <p>Aceptas defender, indemnizar y mantener indemne a Workon, sus directivos y empleados, frente a cualquier reclamo, daño o gasto (incluyendo honorarios legales razonables) que surja de: (a) tu uso de la Plataforma, (b) tu incumplimiento de estos Términos, o (c) cualquier aspecto de la transacción entre tú y tus propios clientes o empleados, incluyendo el uso de la función de geolocalización. Eres responsable de cualquier incumplimiento de estos Términos por parte de tus empleados o cuentas de personal, como si fuera tu propio incumplimiento.</p>
            </section>

            <section>
                <h2>14. Terminación</h2>
                <p class="rounded-lg border border-yellow-400/20 bg-yellow-400/5 p-4 text-sm text-zinc-300"><strong class="text-yellow-400">Lo que significa:</strong> puedes cancelar cuando quieras. Nosotros también, si incumples las reglas.</p>
                <p>Puedes cancelar tu cuenta en cualquier momento desde tu panel. Podemos suspender o terminar tu acceso si incumples estos Términos, sin obligación de reembolso salvo lo indicado en la Sección 4.</p>
            </section>

            <section>
                <h2>15. Modificaciones a los Términos</h2>
                <p>Podemos actualizar estos Términos ocasionalmente. Publicaremos la nueva versión en esta misma página con la fecha de actualización. El uso continuado de la Plataforma después de una modificación constituye tu aceptación de los nuevos Términos.</p>
            </section>

            <section>
                <h2>16. Ley Aplicable y Jurisdicción</h2>
                <p>Estos Términos se rigen por las leyes de [JURISDICCIÓN — ej. "el Estado de Georgia, Estados Unidos"], sin dar efecto a sus disposiciones sobre conflicto de leyes. Cualquier disputa se resolverá en los tribunales competentes de dicha jurisdicción.</p>
            </section>

            <section>
                <h2>17. Contacto</h2>
                <p>Si tienes preguntas sobre estos Términos, escríbenos a <a href="mailto:[EMAIL DE CONTACTO]" class="text-yellow-400 hover:underline">[EMAIL DE CONTACTO]</a>.</p>
            </section>

        </div>

        <a href="{{ route('home') }}" class="mt-14 inline-block text-sm text-zinc-500 hover:text-yellow-400">← Volver al inicio</a>
    </div>
</body>
</html>
