<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad — Workon</title>
    @vite('resources/css/welcome.css')
</head>
<body class="min-h-screen bg-zinc-950 px-6 py-16 text-zinc-300 antialiased sm:px-10">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('home') }}" class="text-2xl font-black tracking-tighter text-yellow-400">Workon</a>

        <h1 class="mt-8 text-3xl font-black tracking-tight text-white sm:text-4xl">Política de Privacidad</h1>
        <p class="mt-2 text-sm text-zinc-500">Última actualización: {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>

        <div class="prose prose-invert mt-10 max-w-none space-y-8 text-zinc-300 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-yellow-400 [&_p]:leading-relaxed [&_p]:text-zinc-400 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6 [&_ul]:text-zinc-400">

            <section>
                <h2>1. Introducción</h2>
                <p>Esta Política de Privacidad explica cómo [RAZÓN SOCIAL], operadora de Workon ("nosotros"), recopila, usa y protege la información cuando usas nuestra plataforma. Aplica tanto a los dueños de negocio que se registran en Workon (Tenants) como a los clientes y empleados de esos negocios cuya información es ingresada a la Plataforma.</p>
            </section>

            <section>
                <h2>2. Información que Recopilamos</h2>
                <ul>
                    <li><strong>Datos de la cuenta:</strong> nombre, email, teléfono y contraseña del Tenant.</li>
                    <li><strong>Datos del negocio:</strong> nombre, dirección, horarios, fotos, servicios, cupones y redes sociales que el Tenant decide publicar.</li>
                    <li><strong>Datos de clientes del Tenant:</strong> información de contacto que el Tenant ingresa para gestionar sus propios clientes y citas.</li>
                    <li><strong>Datos de empleados:</strong> nombre, código de empleado, horario, y registros de entrada/salida, incluyendo <strong>coordenadas de ubicación GPS</strong> cuando el Tenant activa la verificación de ubicación en el módulo de asistencia.</li>
                    <li><strong>Datos de pago:</strong> procesados y almacenados directamente por Stripe; Workon no almacena números de tarjeta completos.</li>
                    <li><strong>Datos técnicos:</strong> dirección IP, tipo de navegador e información de uso de la Plataforma con fines de seguridad y mejora del servicio.</li>
                </ul>
            </section>

            <section>
                <h2>3. Cómo Usamos la Información</h2>
                <ul>
                    <li>Para operar y prestar las funciones de la Plataforma (sitio web, reservas, facturación, asistencia).</li>
                    <li>Para procesar pagos y gestionar suscripciones.</li>
                    <li>Para mostrar tu negocio en el directorio central de búsqueda de Workon (si tu perfil está activo).</li>
                    <li>Para comunicarnos contigo sobre tu cuenta, cambios en el servicio o soporte técnico.</li>
                    <li>Para prevenir fraude y proteger la seguridad de la Plataforma.</li>
                </ul>
            </section>

            <section>
                <h2>4. Geolocalización de Empleados</h2>
                <p>Cuando un Tenant activa la verificación de ubicación en el módulo de clock-in, el dispositivo del empleado envía sus coordenadas GPS en el momento de marcar entrada o salida, únicamente para confirmar que se encuentra dentro del radio configurado para su lugar de trabajo. No realizamos rastreo continuo de ubicación fuera de ese momento puntual. Esta información se almacena asociada al registro de asistencia y es responsabilidad del Tenant informar a sus empleados sobre esta funcionalidad antes de activarla.</p>
            </section>

            <section>
                <h2>5. Con Quién Compartimos tu Información</h2>
                <p>No vendemos tu información personal. La compartimos únicamente con:</p>
                <ul>
                    <li><strong>Stripe</strong>, para procesar pagos y suscripciones.</li>
                    <li>Nuestro proveedor de hosting/infraestructura, para operar la Plataforma.</li>
                    <li>Autoridades, si es legalmente requerido.</li>
                </ul>
            </section>

            <section>
                <h2>6. Retención y Eliminación de Datos</h2>
                <p>Conservamos tu información mientras tu cuenta esté activa. Si eliminas tu cuenta o negocio, borramos los datos asociados (incluyendo imágenes almacenadas) dentro de un plazo razonable, salvo la información que debamos conservar por obligaciones legales o contables.</p>
            </section>

            <section>
                <h2>7. Seguridad</h2>
                <p>Aplicamos medidas técnicas razonables (cifrado en tránsito, aislamiento de datos por negocio mediante bases de datos independientes por Tenant) para proteger tu información. Ningún sistema es 100% seguro, y no podemos garantizar la seguridad absoluta de los datos transmitidos por internet.</p>
            </section>

            <section>
                <h2>8. Tus Derechos</h2>
                <p>Puedes solicitar acceso, corrección o eliminación de tu información personal escribiéndonos a <a href="mailto:[EMAIL DE CONTACTO]" class="text-yellow-400 hover:underline">[EMAIL DE CONTACTO]</a>. Si eres cliente o empleado de un negocio que usa Workon (no el dueño de la cuenta), debes dirigir tu solicitud primero a ese negocio, ya que ellos son quienes administran tu información dentro de la Plataforma.</p>
            </section>

            <section>
                <h2>9. Cookies</h2>
                <p>Usamos cookies esenciales para mantener tu sesión iniciada y recordar tu preferencia de idioma. No usamos cookies de rastreo publicitario de terceros.</p>
            </section>

            <section>
                <h2>10. Cambios a esta Política</h2>
                <p>Podemos actualizar esta Política ocasionalmente. Publicaremos la nueva versión en esta misma página con la fecha de actualización.</p>
            </section>

            <section>
                <h2>11. Contacto</h2>
                <p>Para cualquier pregunta sobre esta Política de Privacidad, escríbenos a <a href="mailto:[EMAIL DE CONTACTO]" class="text-yellow-400 hover:underline">[EMAIL DE CONTACTO]</a>.</p>
            </section>

        </div>

        <a href="{{ route('home') }}" class="mt-14 inline-block text-sm text-zinc-500 hover:text-yellow-400">← Volver al inicio</a>
    </div>
</body>
</html>
