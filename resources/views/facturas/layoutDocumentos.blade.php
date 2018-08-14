<Grid>
    <Cfg id='reporteXML' Style='Light' Code='PTAEXXOQBFRYSC' Sort='comprobante' MaxHeight='1' MinTagHeight="400" ShowDeleted="1" Deleting="0" DateStrings='1'
         IdNames='Project'  AppendId='1' FullId='1' IdChars='0123456789' Selecting='0' NumberId='1' LastId='1' CaseSensitiveId='1'/>
    <LeftCols>
        <C Name='comprobante' Width='100' Type='Lines' CanEdit='0'/>

    </LeftCols>
    <Cols>
        <C Name='serie_comprobante' Width='120' Type='Text' CanEdit='0'/>
        <C Name='ruc_emisor' Width='100' Type='Lines' CanEdit='1'/>
        <C Name='razon_social_emisor' Width='200' Type='Lines' CanEdit='0'/>
        <C Name='fecha_emision' Width='100' Type='Date' CanEdit='0'/>
        <C Name='fecha_autorizacion' Width='100' Type='Date' CanEdit='0'/>
        <C Name='tipo_emision' Width='100' Type='Text' CanEdit='0'/>
        <C Name='documento_relacionado' Width='100' Type='Text' CanEdit='0'/>
        <C Name='identificacion_receptor' Width='100' Type='Text' CanEdit='0'/>
        <C Name='clave_acceso' Width='100' Type='Text' CanEdit='0'/>
        <C Name='numero_autorizador' Width='100' Type='Text' CanEdit='0'/>
        <C Name='mensaje' Width='60' Type='Text' CanEdit='0'/>
        <C Name='voucher_no' Width='100' Type='Text' CanEdit='0'/>
        <C Name='importe_total' Width='100' Type='Text' CanEdit='0'/>
    </Cols>
    <Header  Wrap="1"
             comprobante="Comprobante"
             serie_comprobante="Serie Comprobante"
             ruc_emisor="RUC Emisor"
             razon_social_emisor="Razón Social Emisor"
             fecha_emision="Fecha Emision"
             fecha_autorizacion="Fecha Autorización"
             tipo_emision="Tipo Emisión"
             documento_relacionado="Documento Relacionado"
             identificacion_receptor="Identificación Receptor"
             clave_acceso="Clave Acceso"
             numero_autorizador="Número Autorizador"
             mensaje="EN IFS"
             voucher_no="No. Voucher"
             importe_total="Total Factura"
    />
    <Head>
        <I Kind='Filter' mensaje=''
           mensajeButton='Defaults' mensajeRange='1'
           mensajeDefaults='|*RowsVariable|*FilterOff'
           ruc_emisor=''
           ruc_emisorButton='Defaults' ruc_emisorRange='1'
           ruc_emisorDefaults='|*RowsVariable|*FilterOff'
           comprobante=''
           comprobanteButton='Defaults' comprobanteRange='1'
           comprobanteDefaults='|*RowsVariable|*FilterOff'
           razon_social_emisor=''
           razon_social_emisorButton='Defaults' razon_social_emisorRange='1'
           razon_social_emisorrDefaults='|*RowsVariable|*FilterOff'
        />
    </Head>
    <Toolbar id='toolbarDatos' Cells='Reload,Save,Export,Formula,ExpandAll,CollapseAll'/>
</Grid>
