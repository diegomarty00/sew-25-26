<?php
class Cronometro{
    private $tiempo;
    private $inicio;

    public function __construct(){
        $this->tiempo = 0;
    }

    public function arrancar(){
        $this->inicio = microtime(true);
    }

    public function parar(){
        $this->detencion = microtime(true);

        $this->tiempo = $this->detencion - $this->inicio;
    }

    public function mostrar(){
        $segundos = floor($this->tiempo);
        $decimas = floor(($this->tiempo - $segundos)*10);

        $minutos = floor($segundos / 60);
        $segundos = $segundos % 60;

        echo "<p>Tiempo " . $minutos . "." . $segundos . "." . $decimas . "</p>";
    }

    public function getSegundos(): int{
        return (int) round($this->tiempo);
    }
}


?>