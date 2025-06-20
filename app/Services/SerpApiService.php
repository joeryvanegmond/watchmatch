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
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                ['role' => 'assistant',
                 'content' => $this->CreatePrompt($brand, $model),
                ]
            ]
        ]);
        $rawContent = $rawContent = $response->choices[0]->message->content;
        $watches = json_decode($rawContent);

        return $watches ?? [];
    }



    public function CreatePrompt(string $brand, string $model)
    {
        return <<<EOT
        Je krijgt van mij een horlogemerk en model. 

        Het merk betreft {$brand} en het model is {$model}
        
        Geef mij een lijst van horloges die hier sterk op lijken qua stijl, functionaliteit, prijssegment of doelgroep. Het opgegeven horloge zelf mag niet in de lijst voorkomen. Alle merken en modellen moeten uniek zijn in het resultaat.
        
        Geef het resultaat **uitsluitend** terug in exact dit JSON-formaat, zonder uitleg, tekst of extra tekens ervoor of erna. de json moet ook niet beautified zijn. Het moet simpelweg json zijn verpakt in string en ZONDER andere toelichting etc.
        
        De JSON moet een array zijn van objecten met deze velden:
        
        - "brand" (string): merk van het horloge  
        - "model" (string): modelnaam van het horloge  
        - "variant" (string, optioneel): uitvoering, bijvoorbeeld kleur of materiaal  
        - "price" (decimal): richtprijs in euro's (alleen getal, zonder €-teken)  
        - "url" (string): link naar een pagina waar het horloge gekocht kan worden, dit moet een ECHT werkende link zijn die naar de productpagina gaat van het horloge. Dit mag absoluut niet leeg, nep of een niet werkende link zijn.
        - "image_url" (string): directe URL naar een afbeelding van het horloge, dit moet een ECHT werkende link zijn die naar de afbeelding gaat van het horloge. Dit mag absoluut niet leeg, nep of een niet werkende link zijn.
        
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
}
