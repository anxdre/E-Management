import { useGeolocation } from "@vueuse/core";
import tt from "@tomtom-international/web-sdk-maps";

export class TomTomMapEncoding {
    private readonly _map: tt.Map;

    constructor(container: HTMLElement) {
        this._map = tt.map({
            key: 'YfCCUSubfF0dz5KH5lwkQxQbuCGwKGYy',
            container: container,
            zoom: 20
        })
    }

    get map() {
        return this._map;
    }
}
