class Clasificacion{
    protected $documento;

    public function __construct($documento){
        $this->documento = $documento;
    }

    public function consultar(){
        $archivo = fopen($this->documento, "r");
        

        $datos = file_get_contents($archivo);

        fclose($archivo);

        $xml = new SimpleXMLElement($datos);

        {$xml->clasificacion['name']};
    }
}