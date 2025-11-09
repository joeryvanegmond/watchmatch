<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class SerpApiService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('SERPAPI_API_KEY');
    }

    public function search(string $brand, string $model): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4.1-nano',
                'messages' => [
                    [
                        'role' => 'assistant',
                        'content' => $this->CreateSearchPrompt($brand, $model),
                    ]
                ]
            ]);
            $rawContent = $rawContent = $response->choices[0]->message->content;
            $watches = json_decode($rawContent);
    
            return $watches ?? [];
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function getDescription(string $brand, string $model)
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4.1-nano',
                'messages' => [
                    [
                        'role' => 'assistant',
                        'content' => $this->CreateDescriptionPrompt($brand, $model),
                    ]
                ]
            ]);
            $rawContent = $rawContent = $response->choices[0]->message->content;
            $description = json_decode($rawContent);
    
            return $description ?? null;
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function isGarbage(string $brand, string $model)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                [
                    'role' => 'assistant',
                    'content' => $this->CreateIsGarbagePrompt($brand, $model),
                ]
            ]
        ]);
        $rawContent = $rawContent = $response->choices[0]->message->content;
        $result = json_decode($rawContent);
        return $result->is_watch;
    }


    private function CreateSearchPrompt(string $brand, string $model)
    {
        return <<<EOT
        Je krijgt van mij een horlogemerk en model. 

        Het merk betreft {$brand} en het model is {$model}
        
        Geef mij een lijst van minimaal 15 horloges die hier sterk op lijken qua stijl, functionaliteit, prijssegment of doelgroep. Het opgegeven horloge zelf mag niet in de lijst voorkomen. Alle merken en modellen moeten uniek zijn in het resultaat.
        
        Geef het resultaat **uitsluitend** terug in exact dit JSON-formaat, zonder uitleg, tekst of extra tekens ervoor of erna. de json moet ook niet beautified zijn. Het moet simpelweg json zijn verpakt in string en ZONDER andere toelichting etc.
        
        De JSON moet een array zijn van objecten met deze velden:
        
        - "brand" (string): merk van het horloge  
        - "model" (string): modelnaam van het horloge  
        - "variant" (string, optioneel): uitvoering, bijvoorbeeld kleur of materiaal  
        - "price" (decimal): richtprijs in euro's (alleen getal, zonder €-teken)  
        - "url" (string): link naar een pagina waar het horloge gekocht kan worden, dit moet een ECHT werkende link zijn die naar de productpagina gaat van het horloge. Dit mag absoluut niet leeg, nep of een niet werkende link zijn.
        - "image_url" (string): directe URL naar een afbeelding van het horloge, dit moet een ECHT werkende link zijn die naar de afbeelding gaat van het horloge. Dit mag absoluut niet leeg, nep of een niet werkende link zijn.
        - "description" (string): een korte, natuurlijke beschrijving (maximaal 2 tot 3 zinnen) van dit horloge.  
            De beschrijving moet:
            - op menselijke toon geschreven zijn (niet generiek of robotachtig);  
            - de stijl, het type en het gebruiksdoel kort benoemen (bijv. duikhorloge, dress watch, sportief, chronograaf, vintage geïnspireerd, etc.);  
            - indien mogelijk een opvallend kenmerk noemen (materiaal, wijzerplaatkleur, kaliber, formaat, etc.);  
            - geen loze marketingtaal bevatten ("prachtig", "iconisch", "geweldig", etc. vermijden);  
            - nooit verwijzen naar de prijs of beschikbaarheid;  
            - in neutraal Nederlands geschreven zijn (geen Engelse termen tenzij modelnaam).  

        Voorbeeld van een correcte "description":
        > "De Tudor Black Bay 58 is een compact duikhorloge van 39 mm met een vintage uitstraling en automatisch uurwerk, geïnspireerd op de duikmodellen uit de jaren 50."

        Gebruik **exact deze veldnamen en volgorde**. Lever minimaal 5 en maximaal 10 alternatieve horloges. Zorg dat alle strings juist geescaped zijn. Geef alleen geldige en sluitende JSON terug, zonder tekst erboven of eronder.
        
        Voorbeeldinput:
        merk: Omega  
        model: Speedmaster Moonwatch
        
        Verwachte output:
        [
          {
            "brand": "TAG Heuer",
            "model": "Carrera Chronograph",
            "variant": "Black Dial",
            "price": 4500,
            "url": "https://example.com/tag-heuer-carrera-black",
            "image_url": "https://example.com/images/tag-carrera-black.jpg"
          },
          ...
        ]

        Nogmaals, het is echt van belang dat de alternatieve horloges die je me geeft ECHT de zelfde stijl, zelfde vorm en kleur moeten hebben.
        EOT;
    }

    private function CreateDescriptionPrompt($brand, $model)
    {
        return <<<TEXT
        Je krijgt van mij een horlogemerk en model. 

        Het merk betreft {$brand} en het model is {$model}

        - "description" (string): een korte, natuurlijke beschrijving (maximaal 2 tot 3 zinnen) van dit horloge, IN HET ENGELS.  
            De beschrijving moet:
            - op menselijke toon geschreven zijn (niet generiek of robotachtig);  
            - de stijl, het type en het gebruiksdoel kort benoemen (bijv. duikhorloge, dress watch, sportief, chronograaf, vintage geïnspireerd, etc.);  
            - indien mogelijk een opvallend kenmerk noemen (materiaal, wijzerplaatkleur, kaliber, formaat, etc.);  
            - geen loze marketingtaal bevatten ("prachtig", "iconisch", "geweldig", etc. vermijden);  
            - nooit verwijzen naar de prijs of beschikbaarheid;  
            - in neutraal Nederlands geschreven zijn (geen Engelse termen tenzij modelnaam).  

        Voorbeeld van een correcte "description":
        > "The Tudor Black Bay 58 is a compact 39mm diving watch with a vintage look and automatic movement, inspired by the diving models of the 1950s."

        Verwachte output:
        {
        "description": "The Tudor Black Bay 58 is a compact 39mm diving watch with a vintage look and automatic movement, inspired by the diving models of the 1950s.",
        }
        TEXT;
    }

    private function CreateIsGarbagePrompt($brand, $model)
    {
        return <<<TEXT
        Je bent een API die bepaalt of een gegeven merk en model een horloge is.

        Beantwoord uitsluitend met precies één van deze twee JSON-antwoorden:
        { "is_watch": true }

        of

        { "is_watch": false }
        Gebruik alleen true als zowel het merk als het model samen bekend en bevestigd zijn als een bestaand horloge.
        Als het merk wél bestaat als horlogemerk, maar het opgegeven model niet bestaat of niet bij dat merk hoort, dan is het antwoord altijd:
        { "is_watch": false }
        Als het merk onbekend is, of het model onbekend is, of als merk en model niet bij elkaar horen, geef dan ook:
        { "is_watch": false }
        Als het model-woord lijkt op een horlogenaam, maar het in werkelijkheid geen bestaand horlogemodel is (bijvoorbeeld een woord dat toevallig lijkt maar het niet is), moet het ook worden afgekeurd met { "is_watch": false }.
        Antwoord nooit true als je niet met zekerheid kunt zeggen dat het een bestaand horloge betreft met dat merk en model.
        Voorbeeld:

        Merk: "Tissot"
        Model: "PRX 100"
        Antwoord:
        { "is_watch": true }
        
        Merk: "Tissot"
        Model: "PRX 1000" (lijkt erop maar bestaat niet)
        Antwoord:
        { "is_watch": false }

        Merk: "aquis"
        Model: "etad" (lijkt echt, bestaat niet)
        Antwoord:
        { "is_watch": false }

        Merk: "mido"
        Model: "forest circle" (lijkt echt, bestaat niet)
        Antwoord:
        { "is_watch": false }
        
        Beantwoord nu:
        Merk: "{$brand}"
        Model: "{$model}"
        TEXT;
    }
}
