<Grid>
    <Cfg id='reporteXML' Code='PTAEXXOQBFRYSC' Sort='razonSocial' MaxHeight='1' MinTagHeight="400" ShowDeleted="1" Deleting="0" DateStrings='1'
         IdNames='Project'  AppendId='1' FullId='1' IdChars='0123456789' Selecting='0' NumberId='1' LastId='1' CaseSensitiveId='1'/>

    <Cols>
        <C Name='razonSocial' Width='200' Type='Text' CanEdit='0'/>
        <C Name='ruc' Width='100' Type='Text' CanEdit='0'/>
        <C Name='claveAcceso' Width='200' Type='Text' CanEdit='0'/>
        <C Name='codDoc' Width='80' Type='Text' CanEdit='0'/>
        <C Name='no_factura' Width='200' Type='Text' CanEdit='0'/>
        <C Name='enviado_ifs' Width='200' Type='Text' CanEdit='0'/>
        <C Name='fecha_envio' Width='200' Type='Text' CanEdit='0'/>
        <C Name='path' Width='200' Type='Link' CanEdit='0'/>
    </Cols>
    <Header  Wrap="1"
             razonSocial="Razon Social"
             ruc="RUC"
             claveAcceso="Clave de Acceso"
             codDoc="Cod Doc"
             no_factura="No. Factura"
             enviado_ifs="Enviado IFS"
             fecha_envio="Fecha Envio"
             path="Path"
    />
    <Head>
        <I Kind='Filter' ruc=''
           rucButton='Defaults' rucRange='1'
           rucDefaults='|*RowsVariable|*FilterOff'
        />
    </Head>
    <Toolbar id='toolbarDatos' Cells='Reload,Save,Export,Formula,ExpandAll,CollapseAll'/>
</Grid>
