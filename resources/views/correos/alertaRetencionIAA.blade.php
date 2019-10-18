<table cellspacing="0" cellpadding="0" border="1" width="100%">
    <tbody>
    <tr>
        <td width="14%" rowspan="2" align="center">
            <img height="80" width="120" src="http://appl.santoscmi.com/logoIAA.jpg"/>
        </td>
        <td height="45" width="64%" align="center">
            <strong>INDUSTRIA ACERO DE LOS ANDES</strong>
        </td>
        <td align="center">
            <strong>DATE:</strong>
        </td>

    </tr>
    <tr>
        <td valign="middle" align="center">
            <strong>FACTURAS - PROVEEDORES</strong>
        </td>
        <td width="58%" align="center">
            {{ date("Y-m-d") }}
        </td>
    </tr>
    </tbody>
</table>
<p align="justify" style="padding:5px;">
    @if(count($facturasSinRetencion)>0)
        <b>Facturas Sin Retenciones</b><br>
        @for($i=0;$i<count($facturasSinRetencion);$i++)
            {{$facturasSinRetencion[$i]}}<br>
        @endfor
        <br><br>
    @endif
    @if(count($facturasSinAutorizacion)>0)
        <b>Facturas Sin Autorizacion en la Retención</b><br>
        @for($i=0;$i<count($facturasSinAutorizacion);$i++)
            {{$facturasSinAutorizacion[$i]}}<br>
        @endfor
    @endif<br><br>
    Saludos, <br><br>
</p>
<hr>
<p align="justify" style="color:#aaaaaa;font-size:11px;padding:8px;">
    Please do not print this e-mail, unless necessary. The information contained in this communication is
    confidential, may be subject to legal privileges, may constitute inside information, and is intended
    only for the use of the addressee. It is the property of SANTOS CMI or its relevant subsidiary.
    Unauthorized use, disclosure or copying of this communication or any part thereof is strictly prohibited
    and may be unlawful. If you have received this communication in error, please notify SANTOS CMI immediately
    by return e-mail and destroy this communication and all copies thereof, including all attachments.<br><br>
    Por favor no imprima este correo, a menos que sea necesario.  La informaci&oacute;n contenida en esta comunicaci&oacute;n
    es confidencial, puede estar sujeta a privilegios legales, puede constituir informaci&oacute;n interna, y s&oacute;lo
    est&aacute; dirigida al uso del destinatario.  Es propiedad de SANTOS CMI o su subsidiaria relevante.  El uso,
    divulgaci&oacute;n o copia no autorizada de esta comunicaci&oacute;n de cualquiera de sus partes est&aacute;
    estrictamente prohibida y puede ser contraria a la ley.  Si usted ha recibido esta comunicaci&oacute;n por error,
    por favor notifique a SANTOS CMI inmediatamente mediante un correo electr&oacute;nico y destruya esta comunicaci&oacute;n
    y todas las copias de ella, incluyendo todos los adjuntos.
</p>
