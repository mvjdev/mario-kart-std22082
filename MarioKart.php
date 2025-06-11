<?php

class Kart
{
    public ?string $couleur = null;
    public ?string $moteur = null;

    public function description(): string
    {
        return "Kart couleur {$this->couleur}, moteur {$this->moteur}";
    }

    public function setCouleur(string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function setMoteur(string $moteur): self
    {
        $this->moteur = $moteur;
        return $this;
    }
}

class KartBuilder
{
    private Kart $kart;

    public function __construct()
    {
        $this->kart = new Kart();
    }

    public function setCouleur(string $couleur): self
    {
        $this->kart->setCouleur($couleur);
        return $this;
    }

    public function setMoteur(string $moteur): self
    {
        $this->kart->setMoteur($moteur);
        return $this;
    }

    public function build(): Kart
    {
        if (!$this->kart->couleur || !$this->kart->moteur) {
            throw new Exception("Le kart doit avoir une couleur et un moteur.");
        }
        return $this->kart;
    }
}

interface Strategy
{
    public function move(int $position, int $dice): int;
}

class BalancedMove implements Strategy
{
    public function move(int $position, int $dice): int
    {
        return $position + 2 * $dice;
    }
}

class StableMove implements Strategy
{
    public function move(int $position, int $dice): int
    {
        return $position + 1 * $dice;
    }
}

class FastMove implements Strategy
{
    public function move(int $position, int $dice): int
    {
        return $position + 3 * $dice;
    }
}

class Driver
{
    public string $name;
    public int $speed;
    public int $stability;
    public int $position = 0;
    public Strategy $strategy;
    public Kart $kart;

    public function __construct(string $name, int $speed, int $stability, Strategy $strategy, Kart $kart)
    {
        $this->name = $name;
        $this->speed = $speed;
        $this->stability = $stability;
        $this->strategy = $strategy;
        $this->kart = $kart;
    }

    public function playTurn(): void
    {
        $dice = rand(1, 6);
        echo " {$this->name} lance le dé : $dice\n";

        if ($dice == 6 && $this->stability < 2) {
            echo "{$this->name} a glissé ! Il ne bouge pas ce tour.\n";
            return;
        }

        $oldPos = $this->position;
        $this->position = $this->strategy->move($this->position, $dice);
        if ($this->position > 19) $this->position = 19;

        echo "--> {$this->name} passe de $oldPos à {$this->position}\n";
    }

    public function renderTrack(): void
    {
        $track = '';
        for ($i = 0; $i < 20; $i++) {
            $track .= $i === $this->position ? 'J ' : '_ ';
        }
        echo $track . "\n";
    }
}


class DriverFactory
{
    public static function create(string $choice, Kart $kart): ?Driver
    {
        return match (strtolower($choice)) {
            'mario' => new Driver("Mario", 2, 2, new BalancedMove(), $kart),
            'luigi' => new Driver("Luigi", 1, 3, new StableMove(), $kart),
            'peach' => new Driver("Peach", 3, 1, new FastMove(), $kart),
            default => null,
        };
    }
}


class Game
{
    public function start(): void
    {
        echo "Bienvenue dans Mario Kart Simulator !\n";

        echo "Choisissez votre pilote (mario / luigi / peach) :\n> ";
        $stdin = fopen("php://stdin", "r");
        $choice = trim(fgets($stdin));

        // === CHOIX COULEUR VALIDE ===
        $validColors = ['rouge', 'vert', 'bleu'];
        do {
            echo "Choisissez la couleur de votre kart (rouge / vert / bleu) :\n> ";
            $color = strtolower(trim(fgets($stdin)));
            if (!in_array($color, $validColors)) {
                echo "Couleur invalide. Veuillez choisir entre rouge, vert ou bleu.\n";
            }
        } while (!in_array($color, $validColors));

        // === CHOIX MOTEUR LIBRE ===
        echo "Choisissez le type de moteur (Standard / Turbo / Électrique) :\n> ";
        $engine = trim(fgets($stdin));

        try {
            $kart = (new KartBuilder())
                ->setCouleur($color)
                ->setMoteur($engine)
                ->build();
        } catch (Exception $e) {
            echo "Erreur lors de la création du kart : " . $e->getMessage() . "\n";
            return;
        }

        $driver = DriverFactory::create($choice, $kart);

        if (!$driver) {
            echo "Pilote inconnu. Réessayez avec mario / luigi / peach.\n";
            return;
        }

        echo "\nDépart ! {$driver->name} dans son kart ({$driver->kart->description()})\n";
        echo "Objectif : atteindre la case 20\n\n";

        while ($driver->position < 19) {
            echo "\nAppuyez sur Entrée pour jouer...";
            fgets($stdin);
            $driver->playTurn();
            $driver->renderTrack();
        }

        echo "\n{$driver->name} a gagné la course ! Bravo\n";
        fclose($stdin);
    }
}


$game = new Game();
$game->start();
