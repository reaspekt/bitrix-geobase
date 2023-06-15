if (typeof(JCReaspektGeobase) == "undefined") {
    var JCReaspektGeobase = {
        params: {},
        letters: "",
        timer: "0",
        init: function(params = {}) {
            if (typeof params === 'object') {
                this.params = params;
            }
        },
        onClickReaspektGeobase: function(city_id) {  
            let obj = this;  
            obj.showPreloaderReaspektGeobase();
            BX.ajax.runAction('reaspekt:geobase.api.setCity', {
                data: {
                    'cityID': city_id
                }
            }).then(function(response) {
                if (response.data.STATUS == "Y") {
                    console.log('close');
                    obj.onClickReaspektSaveCity("Y");
                } else {
                    console.log('Error, change city!');
                }
            }, function(response) {
                console.log(response);
            });
        },
        onClickReaspektSaveCity: function(reload) {
            let wrapQuestion = BX("wrapQuestionReaspekt");
            BX.style(wrapQuestion, 'display', 'none');

            BX.ajax.runAction('reaspekt:geobase.api.saveCity', {
                data: {
                    'sessid': BX.bitrix_sessid()
                }
            }).then(function(response) {
                if (response.data.STATUS == "Y") {
                    console.log('save');
                    BX.remove(wrapQuestion);

                    if (reload == "Y") {
                        document.location.reload();
                    }
                } else {
                    console.log('Error, no save change!');
                }
            }, function(response) {
                console.log(response);
            });
        },
        inpKeyReaspektGeobase: function(e) {
            e = e||window.event;

            var t = (window.event) ? window.event.srcElement : e.currentTarget;
            var sFind = BX.util.trim(t.value);

            if (this.letters == sFind) {
                return; // if nothing has changed, do not do heavy load server
            }

            this.letters = sFind;

            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = 0;
            }

            if (this.letters.length < 2) {
                return;
            }

            this.timer = window.setTimeout(this.loadReaspektGeobase.bind(this), 190);
        },
        showPreloaderReaspektGeobase: function() {
            document.getElementsByClassName("preloaderReaspekt")[0].classList.add("active");
        },
        hidePreloaderReaspektGeobase: function() {
            document.getElementsByClassName("preloaderReaspekt")[0].classList.remove("active");
        },
        loadReaspektGeobase: function() {
            let obj = this;
            obj.showPreloaderReaspektGeobase();

            this.timer = 0;
            var list = BX('reaspektGeobaseFind');

            BX.ajax.runAction('reaspekt:geobase.api.showSearchedCity', {
                data: {
                    'cityName': this.letters
                },
                timeout: 10000
            }).then(function(response) {
                if (BX('reaspektResultCityAjax') !== null) {
                    BX('reaspektResultCityAjax').remove();
                }
                BX.append(
                    BX.create('div', {props: {id: 'reaspektResultCityAjax'}, html: response.data}),
                    list
                );
                obj.hidePreloaderReaspektGeobase();
            }, function(response) {
                console.log(response);
            });
        }
    }
}