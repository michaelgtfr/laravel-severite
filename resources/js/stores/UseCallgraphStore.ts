import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import GridModel from '@/models/GridModel';
import type { IdentityCard } from '@/types/callgraph';

export const useCallgraphStore = defineStore('identityCard', () => {
    //* State
    const identityCards = ref<Record<string, IdentityCard>>({});

    const totalMetric = ref(null);

    const identityCardCallgraph = ref({});

    const grid = ref<GridModel | null>(null);

    const metricChosen = ref<string | null>(null);

    const methodChosen = ref<string | null>(null);

    const detailMetricChosen = ref<string | null>(null);

    const listMethodOfReport = ref<string[]>([]);

    const getMectricChosen: ComputedRef<string | null> = computed(
        () => metricChosen.value,
    );

    const getMethodChosen: ComputedRef<string | null> = computed(
        () => methodChosen.value,
    );

    const getDetailMetricChosen: ComputedRef<string | null> = computed(
        () => detailMetricChosen.value,
    );

    const getListMethodOfReport: ComputedRef<string[]> = computed(
        () => listMethodOfReport.value,
    );

    const getIdentityCardCallgraph = computed(
        () => identityCardCallgraph.value,
    );

    const setMetricChosen = (value: string | null) => {
        metricChosen.value = value;
    };

    const setMethodChosen = (value: string | null) => {
        methodChosen.value = value;
    };

    const setListMethodOfReport = (value: string) => {
        listMethodOfReport.value.push(value);
    };

    const setDetailMetricChosen = (value: string) => {
        detailMetricChosen.value = value;
    };

    /**
     * Layout hiérarchique (Sugiyama simplifié) pour le callgraph.
     *
     * Étape 1 — couche Y : tri topologique + relaxation des arêtes
     *   → chaque nœud est à la profondeur maximale depuis la racine.
     *
     * Étape 2 — ordre X dans chaque couche : heuristique barycentre
     *   → un nœud est placé selon la moyenne des positions X de ses parents,
     *     ce qui minimise les croisements d'arêtes.
     *
     * Étape 3 — conversion en positions pixel dans Callgraph.vue (voir nodeFunction).
     */
    const hydrateIdentityCardWithPosition = (
        identityCardsCollection: IdentityCard[],
    ) => {
        grid.value = new GridModel();

        if (!identityCardsCollection.length) {
            return grid.value;
        }

        // --- Setup ---
        const allKeys = new Set<string>(
            identityCardsCollection.map((ic) => ic.globalMetrics.key),
        );

        const effectiveParents = new Map<string, string[]>();
        const children = new Map<string, string[]>();

        allKeys.forEach((k) => {
            effectiveParents.set(k, []);
            children.set(k, []);
        });

        identityCardsCollection.forEach((ic) => {
            const key = ic.globalMetrics.key;
            const parents = ic.parentFunction.filter((p) => allKeys.has(p));

            effectiveParents.set(key, parents);
            parents.forEach((p) => children.get(p)!.push(key));
        });

        // --- Étape 0.5 : détection des back-edges pour casser les cycles ---
        // Le graphe d'appels peut contenir des cycles (ex: conteneur IoC de Laravel).
        // Un BFS de tri topologique échoue sur les cycles : les nœuds cycliques restent
        // à in_degree > 0 et ne sont jamais traités, restant à la couche 0.
        // On fait un DFS itératif depuis les racines pour marquer les back-edges
        // (arêtes qui ferment un cycle). Ces arêtes sont ensuite ignorées dans le BFS.
        const backEdges = new Set<string>(); // format "source\x00target"

        {
            const visited = new Set<string>();
            const inStack = new Set<string>();

            // DFS itératif pour éviter les débordements de pile sur de grands graphes
            const dfsStack: Array<{ key: string; childIdx: number }> = [];

            const startDfs = (root: string) => {
                if (visited.has(root)) {
return;
}

                visited.add(root);
                inStack.add(root);
                dfsStack.push({ key: root, childIdx: 0 });

                while (dfsStack.length > 0) {
                    const frame = dfsStack[dfsStack.length - 1];
                    const childList = children.get(frame.key) ?? [];

                    if (frame.childIdx < childList.length) {
                        const child = childList[frame.childIdx];
                        frame.childIdx++;

                        if (!visited.has(child)) {
                            visited.add(child);
                            inStack.add(child);
                            dfsStack.push({ key: child, childIdx: 0 });
                        } else if (inStack.has(child)) {
                            backEdges.add(`${frame.key}\x00${child}`);
                        }
                    } else {
                        inStack.delete(frame.key);
                        dfsStack.pop();
                    }
                }
            };

            // Partir des racines (nœuds sans parents effectifs), puis couvrir le reste
            allKeys.forEach((k) => {
                if (effectiveParents.get(k)!.length === 0) {
startDfs(k);
}
            });
            allKeys.forEach((k) => startDfs(k));
        }

        // --- Étape 1 : couches Y via tri topologique + longest-path ---
        // On utilise uniquement les arêtes non-back (DAG acyclique)
        const inDegree = new Map<string, number>();
        const layers = new Map<string, number>();

        allKeys.forEach((k) => {
            const nonBackParents = effectiveParents
                .get(k)!
                .filter((p) => !backEdges.has(`${p}\x00${k}`));
            inDegree.set(k, nonBackParents.length);
            layers.set(k, 0);
        });

        const queue: string[] = [];

        allKeys.forEach((k) => {
            if (inDegree.get(k) === 0) {
                queue.push(k);
            }
        });

        while (queue.length > 0) {
            const key = queue.shift()!;
            const currentLayer = layers.get(key)!;

            children.get(key)!.forEach((child) => {
                if (backEdges.has(`${key}\x00${child}`)) {
return;
}

                const candidate = currentLayer + 1;

                if (candidate > layers.get(child)!) {
                    layers.set(child, candidate);
                }

                const remaining = inDegree.get(child)! - 1;

                inDegree.set(child, remaining);

                if (remaining === 0) {
                    queue.push(child);
                }
            });
        }

        // --- Étape 2 : regroupement par couche ---
        const layerGroups = new Map<number, string[]>();

        identityCardsCollection.forEach((ic) => {
            const key = ic.globalMetrics.key;

            setListMethodOfReport(key);

            const y = layers.get(key) ?? 0;

            if (!layerGroups.has(y)) {
                layerGroups.set(y, []);
            }

            layerGroups.get(y)!.push(key);
        });

        // --- Étape 3 : ordre X par heuristique barycentre (top → bas) ---
        const xOf = new Map<string, number>();
        const sortedLayerIdx = Array.from(layerGroups.keys()).sort(
            (a, b) => a - b,
        );

        sortedLayerIdx.forEach((layerIdx) => {
            const nodes = layerGroups.get(layerIdx)!;

            if (layerIdx > 0) {
                nodes.sort((a, b) => {
                    const bary = (key: string): number => {
                        const xs = effectiveParents
                            .get(key)!
                            .filter((p) => xOf.has(p))
                            .map((p) => xOf.get(p)!);

                        return xs.length
                            ? xs.reduce((sum, x) => sum + x, 0) / xs.length
                            : Infinity;
                    };

                    return bary(a) - bary(b);
                });
            }

            nodes.forEach((key, idx) => xOf.set(key, idx));
        });

        // --- Étape 3b : centrage des parents au-dessus de leurs enfants ---
        // Positions flottantes + passes de relaxation alternées (bottom-up / top-down).
        const xPos = new Map<string, number>(xOf);

        for (let pass = 0; pass < 3; pass++) {
            // Passe montante : chaque parent se centre sur la moyenne X de ses enfants
            [...sortedLayerIdx].reverse().forEach((layerIdx) => {
                const nodes = layerGroups.get(layerIdx)!;

                nodes.forEach((key) => {
                    const childXs = children
                        .get(key)!
                        .filter((c) => xPos.has(c))
                        .map((c) => xPos.get(c)!);

                    if (childXs.length > 0) {
                        xPos.set(
                            key,
                            childXs.reduce((s, x) => s + x, 0) / childXs.length,
                        );
                    }
                });

                // Résoudre les chevauchements de gauche à droite
                nodes.sort((a, b) => (xPos.get(a) ?? 0) - (xPos.get(b) ?? 0));
                nodes.forEach((key, idx) => {
                    if (idx > 0) {
                        const prev = xPos.get(nodes[idx - 1])!;

                        if ((xPos.get(key) ?? 0) < prev + 1.0) {
                            xPos.set(key, prev + 1.0);
                        }
                    }
                });
            });

            // Passe descendante : résorber les chevauchements introduits
            sortedLayerIdx.forEach((layerIdx) => {
                const nodes = layerGroups.get(layerIdx)!;
                nodes.sort((a, b) => (xPos.get(a) ?? 0) - (xPos.get(b) ?? 0));
                nodes.forEach((key, idx) => {
                    if (idx > 0) {
                        const prev = xPos.get(nodes[idx - 1])!;

                        if ((xPos.get(key) ?? 0) < prev + 1.0) {
                            xPos.set(key, prev + 1.0);
                        }
                    }
                });
            });
        }

        // Normaliser : ramener le minimum X à 0
        const minX = xPos.size ? Math.min(...Array.from(xPos.values())) : 0;
        xPos.forEach((val, key) => xPos.set(key, val - minX));

        // --- Étape 4 : écriture des positions + remplissage de la grille ---
        identityCardsCollection.forEach((ic) => {
            const key = ic.globalMetrics.key;
            const x = xPos.get(key) ?? 0;
            const y = layers.get(key) ?? 0;

            ic.callgraph = { xPosition: x, yPosition: y };
            grid.value!.blockPosition(Math.round(x), y, key);
            identityCards.value[key] = ic;
        });

        return grid.value;
    };

    const setIdentityCardCallgraph = (cards: IdentityCard[]) => {
        identityCardCallgraph.value = cards;
    };

    return {
        identityCardCallgraph,
        metricChosen,
        methodChosen,
        totalMetric,

        //*Getters
        getMectricChosen,
        getMethodChosen,
        getListMethodOfReport,
        getIdentityCardCallgraph,
        getDetailMetricChosen,

        //*Setters
        setMetricChosen,
        setMethodChosen,
        setIdentityCardCallgraph,
        setDetailMetricChosen,

        hydrateIdentityCardWithPosition,
    };
});
