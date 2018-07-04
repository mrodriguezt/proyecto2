<Grid>
    <Cfg id='facturacionElectronica' Style='Light' Code='PTAEXXOQBFRYSC' Sort='fecha_emision' MaxHeight='1' Selecting='1' MinTagHeight="400" ShowDeleted="1" Deleting="0" DateStrings='1'
          AppendId='1' FullId='1' IdChars='0123456789' NumberId='1' LastId='1' CaseSensitiveId='1'/>
    <Cols>
          <C Name='fecha_emision' Width='100' Type='Text' CanEdit='0'/>
          <C Name='serie' Width='100' Type='Text' CanEdit='0'/>
          <C Name='no_factura' Width='200' Type='Text' CanEdit='0'/>
          <C Name='cliente' Width='200' Type='Text' CanEdit='0'/>
          <C Name='valor_sin_imp' Width='100' Type='Float' CanEdit='0' Format='$#,###.##'/>
          <C Name='valor_inc_imp' Width='100' Type='Float' CanEdit='0' Format='$#,###.##'/>
          <C Name='fecha_envio' Width='100' Type='Text' CanEdit='0'/>
          <C Name='envio' Width='100' Type='Text' CanEdit='0'/>
          <C Name='status' Width='100' Type='Text' CanEdit='0'/>
    </Cols>
    <Header  Wrap="1"  Main ="1" Rows="2"
             fecha_emision="Fecha Emision"
             no_factura="No. Factura"
             cliente="Cliente"
             valor_sin_imp="Valor Sin Imp"
             valor_inc_imp="Valor Inc Imp"
             fecha_envio="Fecha Envio"
             envio="Envio"
             status="Status"
             serie="Serie"
    />
    <Head>
        <I Kind='Filter'
        />
    </Head>
    <Toolbar id="toolbarDatos" Cells="Save,Export,Reload,Enviar_XML,Actualizar_IFS,Formula"  Space="-1"
             Enviar_XMLType="Button"
             Enviar_XMLButton="Button"
             Enviar_XMLOnClick="enviarXML(Grid)" Enviar_XML="Enviar Tandi-Invoice"
             Actualizar_IFSType="Button"
             Actualizar_IFSButton="Button"
             Actualizar_IFSOnClick="actualizarIFS(Grid)" Actualizar_IFS="ACTUALIZAR IFS"

    />
</Grid>