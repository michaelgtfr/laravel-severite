<?php

namespace Severite\Services;

use Severite\Models\XhprofReport;

class XhprofReportService
{
    private string $mainFunction = 'main()';

    private array $reportNormalized = [];

    private array $reportNormalizedSorted = [];

    private $principalMetricsInPercentage = [
        'mu',
        'wt',
        'cpu',
        'pmu'
    ];

    public function deleteReport(string $reportId)
    {
        return XhprofReport::destroy($reportId);
    }

    public function normalizeXhprofData(string $xhprofReportId)
    {
        $xhprofReport = XhprofReport::find($xhprofReportId);

        $this->normalizeFunction($xhprofReport->report);
        $this->createGlobalMetricByFunction();
        $this->setChildrenFunctionInFunction();
        $this->setExcludeMetricsAndPercentageMetrics();
        $this->sortParentChildren();

        return $this->reportNormalizedSorted;
    }

    /**
     * todo:  faire une tableau de de fonction avec leur metric
     */
    public function normalizeFunction(array $report)
    {
        foreach($report as $key => $value) {
            [$currentFunction, $parentFunction] = $this->getCurrentAndParentFunctionOnKey($key);

            $this->setFunctionAndMetricsInReportNormalized($currentFunction, $parentFunction, $value);
        }
    }

    /**
     * todo: sur chaque ligne faire une coupe via le symbole '==>'
     * ! attention la fonction main n'a pas de caractere '==>' le main etant le total de la fonction
     * * le nom de l'object sera l'enfant (current function)
     */
    public function getCurrentAndParentFunctionOnKey($key): array
    {
        $functions = explode('==>', $key);

        if(count($functions) === 2) {
            $parentFunction = $functions[0];
            $currentFunction = $functions[1];
        } else {
            $currentFunction = $functions[0];
            $parentFunction = null;
        }

        return [$currentFunction, $parentFunction];
    }

    /**
     * todo: si la foncton existe on ajoute juste le parent , sinon on creer un objet et on ajoute le parent
     * todo: ajouter pour chaque fonction courant ces parametres
     */
    public function setFunctionAndMetricsInReportNormalized($currentFunction, $parentFunction, $value): void
    {
        if(isset($this->reportNormalized[$currentFunction])) {
            $this->reportNormalized[$currentFunction]['metrics'][$parentFunction] = $value;
        } else {
            if($parentFunction) {
                $this->reportNormalized[$currentFunction]['metrics'][$parentFunction] = $value;
            } else {
            $this->reportNormalized[$currentFunction]['metrics'][$currentFunction] = $value;
            }
        }
    }

    /**
     * * dans le cas ou il y a plusieurs parents, il faut faire la somme des données des parametres
     *  todo: faire une variable total sur toute les metric des parents
     * todo: le pourcentage total par function par rapport a la fonction main
     * todo: exclude sur chaque fonction
     */
    public function createGlobalMetricByFunction()
    {
        foreach($this->reportNormalized as $key => $value) {
            $metrics = $this->reportNormalized[$key]['metrics'];

            $globalMetricsByFunction = [];
            $parentFunction = [];

            foreach ($metrics as $parentKey => $value) {
                //! attention il faut gere le cas main qui se met en parentFunction
                $parentFunction[] = $parentKey;

                foreach ($value as $keyMetrics => $valueMetrics) {
                    $globalMetricsByFunction[$keyMetrics] = ($globalMetricsByFunction[$keyMetrics] ?? 0) + $valueMetrics;
                }
            }

            $this->reportNormalized[$key]['globalMetrics'] = $globalMetricsByFunction;
            $this->reportNormalized[$key]['parentFunction'] = $key === $this->mainFunction ? [] :  $parentFunction;
        }
    }

    /**
     * todo: boucler sur chaque element est verifier si celle ci et l'enfant d'une autre fonction si il les alors mettre le nom de la fonction dans le table childFunction
     */
    public function setChildrenFunctionInFunction()
    {

        foreach($this->reportNormalized as $childKey => $childValue) {
            $childFunction = [];

            foreach($this->reportNormalized as $key => $value) {
                if(in_array($childKey, $this->reportNormalized[$key]['parentFunction'])) {
                   $childFunction[] = $key;
                }
            }

            $this->reportNormalized[$childKey]['childFunction'] = $childFunction;
        }
    }

    /**
     * ! pour faire le exclude il me faut la liste des enfants
     * todo: mettre les données global en pourcentage et les exclu
     */
    public function setExcludeMetricsAndPercentageMetrics()
    {
        foreach($this->reportNormalized as $key => $value) {
            $mainFunction = $this->reportNormalized[$this->mainFunction];

            foreach($this->principalMetricsInPercentage as $metric) {
                $this->reportNormalized[$key]['globalMetrics']["$metric-total-percentage"] =
                    !empty($mainFunction['globalMetrics'][$metric])
                        ? ($this->reportNormalized[$key]['globalMetrics'][$metric] * 100) / $mainFunction['globalMetrics'][$metric]
                        : 0;
            }

            //todo: passer sur les enfant pour recuperer les valeur de la metric lies aux parent et tous les assembler pour creer une valeur exclue
            //todo: REPRENDRE ICI

            $child = $this->reportNormalized[$key]['childFunction'];

            $sumChildMetricsForThisCurrentFunction = [];

            //todo: si il n'y a pas d'enfant alors creer des exclude a 0
            if(!$child) {
                foreach($this->principalMetricsInPercentage as $keyMetrics) {
                    $sumChildMetricsForThisCurrentFunction["$keyMetrics-excl"] = 0;
                }
            } else {
                            //todo boucle sur chaque enfant
                            foreach($child as $childValue) {
                                //todo: recupere la metric lie a la fonction courant
                                $childMetrics = $this->reportNormalized[$childValue]['metrics'][$key];

                                //todo: la met en place dans un attribut
                                foreach($this->principalMetricsInPercentage as $keyMetrics) {
                                    if(!isset($sumChildMetricsForThisCurrentFunction["$keyMetrics-excl"])) {
                                        $sumChildMetricsForThisCurrentFunction["$keyMetrics-excl"] = 0;
                                    }

                                    $sumChildMetricsForThisCurrentFunction["$keyMetrics-excl"] += $childMetrics[$keyMetrics];
                                }
                            }
            }

            $this->reportNormalized[$key]['globalMetrics'] += $sumChildMetricsForThisCurrentFunction;

            foreach($this->principalMetricsInPercentage as $metric) {
                $this->reportNormalized[$key]['globalMetrics']["$metric-excl-percentage"] =
                    !empty($mainFunction['globalMetrics'][$metric])
                        ? ($this->reportNormalized[$key]['globalMetrics']["$metric-excl"] * 100) / $mainFunction['globalMetrics'][$metric]
                        : 0;
            }
        }
    }

    //todo trier par parent/enfant
    //todo: ce que l'on sait c'est que tout les object on un tableau de lien avec leur enfant
    // todo: donc on recupere le premier puis on boucle puis on recupere c\est enfant
    //! attention il faut que l'on filtre la premer qui a main
    // * ce que l'on peu faire aussi pour eviter les doublons c'est de surveiller dans la dexieme list
    //* que l'objet n'a pas encore ete recuperé
    public function sortParentChildren()
    {
        $firstFunction = $this->reportNormalized[$this->mainFunction];

        $this->reportNormalizedSorted[$this->mainFunction] =  $firstFunction;


        //todo: on reprend avec c'est enfant
        foreach($firstFunction['childFunction'] as $childNameFunction) {
            $this->sortParentChildrenRecursive($childNameFunction);
        }
    }

    public function sortParentChildrenRecursive($nameFunction)
    {
        //* on verifie que la fonctin n'a pas etait recuperer avant
        //todo: on pourrais plutot deplacer plus bas pour que dans le callgrah il ne soit pas plus haut qu'un autre
        //! part contre attention si on deplace au fonction recursive qui se rappelle, a voir
        if(isset($this->reportNormalizedSorted[$nameFunction])) {
           return;
        }

        //todo: on recuper la fonction enfant
        $childFunction = $this->reportNormalized[$nameFunction];

        //todo: on la met dans la deuxieme list
        $this->reportNormalizedSorted[$nameFunction] =  $childFunction;

        //todo: on reprend avec c'est enfant
        foreach($childFunction['childFunction'] as $childNameFunction) {
            $this->sortParentChildrenRecursive($childNameFunction);
        }
    }
}
