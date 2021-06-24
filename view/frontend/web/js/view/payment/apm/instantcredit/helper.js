define([],
    function () {
        'use strict';

        return {
            isBetweenLimits: function(price)
            {
                let icSim = window.icSimulatorCustom;
                if (icSim !== undefined) {
                    let upper = (icSim.upperLimit === undefined) ? 0 : parseFloat(icSim.upperLimit);
                    let lower = (icSim.upperLimit === undefined) ? 0 : parseFloat(icSim.lowerLimit);

                    if (upper === 0 && lower === 0) {
                        return true;
                    } else {
                        return (price >= lower && price <= upper);
                    }
                }
            },
        };
    }
);