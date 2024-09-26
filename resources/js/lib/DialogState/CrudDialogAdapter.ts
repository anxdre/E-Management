import {DialogState} from "@/lib/DialogState/DialogState";

export class CrudDialogAdapter {
    private readonly _add: DialogState
    private readonly _show: DialogState
    private readonly _update: DialogState
    private readonly _delete: DialogState
    private readonly _deleteAll: DialogState

    constructor() {
        this._add = {
            state: false,
            data: {},
            progress: false
        }
        this._show = {
            state: false,
            data: {},
            progress: false
        }
        this._update = {
            state: false,
            data: {},
            progress: false
        }
        this._delete = {
            state: false,
            data: {},
            progress: false
        }
        this._deleteAll = {
            state: false,
            data: {},
            progress: false
        }
    }


    get add(): DialogState {
        return this._add;
    }

    get show(): DialogState {
        return this._show;
    }

    get update(): DialogState {
        return this._update;
    }

    get delete(): DialogState {
        return this._delete;
    }

    get deleteAll(): DialogState {
        return this._deleteAll;
    }

    resetAllState(){
        this.add.state = false
        this.delete.state = false
        this.deleteAll.state = false
        this.update.state = false
    }
}
